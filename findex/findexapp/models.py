from django.db import models
from django.contrib.auth.models import AbstractUser
from django.utils import timezone
from django.core.validators import RegexValidator
import os


class User(AbstractUser):
    """Extended User model with role management"""
    ROLE_CHOICES = [
        ('viewer', 'Viewer'),
        ('editor', 'Editor'),
        ('full_control', 'Full Control'),
        ('admin', 'Admin'),
    ]
    
    role = models.CharField(max_length=20, choices=ROLE_CHOICES, default='viewer')
    must_change_password = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.username} ({self.get_role_display()})"


class Project(models.Model):
    """Project model for document categorization"""
    name = models.CharField(max_length=100, unique=True)
    description = models.TextField(blank=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)

    class Meta:
        ordering = ['name']

    def __str__(self):
        return self.name


class DocType(models.Model):
    """Document type model"""
    name = models.CharField(max_length=100, unique=True)
    description = models.TextField(blank=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['name']

    def __str__(self):
        return self.name


class ExportControl(models.Model):
    """Export control classification model"""
    name = models.CharField(max_length=100, unique=True)
    code = models.CharField(max_length=20, unique=True)
    description = models.TextField(blank=True)
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['code']

    def __str__(self):
        return f"{self.code} - {self.name}"


def document_upload_path(instance, filename):
    """Generate upload path for documents"""
    return f'documents/{instance.project.name}/{filename}'


class Document(models.Model):
    """Main document model with all required fields"""
    # Required fields
    doc_type = models.ForeignKey(DocType, on_delete=models.PROTECT)
    document_file = models.FileField(upload_to=document_upload_path)
    version = models.CharField(max_length=50)
    publish_date = models.DateField(auto_now_add=True)
    project = models.ForeignKey(Project, on_delete=models.PROTECT)
    export_control = models.ForeignKey(ExportControl, on_delete=models.PROTECT)
    
    # Optional fields
    barcode_number = models.CharField(max_length=50, blank=True, unique=True, null=True)
    output_date = models.DateField(blank=True, null=True)
    
    # Multi-language titles (at least one required)
    title_de = models.CharField(max_length=200, blank=True)
    title_en = models.CharField(max_length=200, blank=True)
    title_fr = models.CharField(max_length=200, blank=True)
    
    # Status and description
    is_active = models.BooleanField(default=True)
    description = models.TextField(blank=True)
    
    # Metadata
    uploaded_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)
    uploaded_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    
    # File metadata
    file_size = models.BigIntegerField(null=True, blank=True)
    file_type = models.CharField(max_length=10, blank=True)

    class Meta:
        ordering = ['-uploaded_at']

    def __str__(self):
        title = self.title_de or self.title_en or self.title_fr or 'Untitled'
        return f"{title} ({self.version})"

    def save(self, *args, **kwargs):
        if self.document_file:
            self.file_size = self.document_file.size
            self.file_type = os.path.splitext(self.document_file.name)[1][1:].upper()
        super().save(*args, **kwargs)

    @property
    def primary_title(self):
        """Returns the first available title"""
        return self.title_de or self.title_en or self.title_fr or 'Untitled'

    @property
    def file_icon(self):
        """Returns appropriate icon class based on file type"""
        icon_map = {
            'PDF': 'fa-file-pdf',
            'DOC': 'fa-file-word',
            'DOCX': 'fa-file-word',
            'XLS': 'fa-file-excel',
            'XLSX': 'fa-file-excel',
            'PPT': 'fa-file-powerpoint',
            'PPTX': 'fa-file-powerpoint',
            'TXT': 'fa-file-text',
            'JPG': 'fa-file-image',
            'JPEG': 'fa-file-image',
            'PNG': 'fa-file-image',
            'GIF': 'fa-file-image',
            'BMP': 'fa-file-image',
            'TIFF': 'fa-file-image',
            'TIF': 'fa-file-image',
        }
        return icon_map.get(self.file_type, 'fa-file')


class BarcodeRange(models.Model):
    """Barcode range management"""
    prefix = models.CharField(max_length=10)
    start_number = models.IntegerField()
    end_number = models.IntegerField()
    current_number = models.IntegerField()
    is_active = models.BooleanField(default=True)
    created_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.prefix}: {self.start_number}-{self.end_number}"

    @property
    def is_exhausted(self):
        return self.current_number > self.end_number


class BarcodeAssignment(models.Model):
    """Track barcode assignments"""
    barcode_number = models.CharField(max_length=50, unique=True)
    barcode_range = models.ForeignKey(BarcodeRange, on_delete=models.CASCADE)
    assigned_to = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)
    assigned_at = models.DateTimeField(auto_now_add=True)
    purpose = models.CharField(max_length=200)
    notes = models.TextField(blank=True)
    is_used = models.BooleanField(default=False)

    class Meta:
        ordering = ['-assigned_at']

    def __str__(self):
        return f"{self.barcode_number} - {self.purpose}"


class SystemSettings(models.Model):
    """System-wide settings"""
    key = models.CharField(max_length=100, unique=True)
    value = models.TextField()
    description = models.CharField(max_length=200, blank=True)
    updated_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.key}: {self.value}"

    @classmethod
    def get_setting(cls, key, default=None):
        try:
            return cls.objects.get(key=key).value
        except cls.DoesNotExist:
            return default

    @classmethod
    def set_setting(cls, key, value, user=None, description=''):
        setting, created = cls.objects.get_or_create(
            key=key,
            defaults={'value': value, 'description': description, 'updated_by': user}
        )
        if not created:
            setting.value = value
            setting.updated_by = user
            setting.save()
        return setting


class UserActivity(models.Model):
    """Track user activities for audit purposes"""
    ACTION_CHOICES = [
        ('login', 'Login'),
        ('logout', 'Logout'),
        ('upload', 'Document Upload'),
        ('download', 'Document Download'),
        ('edit', 'Document Edit'),
        ('delete', 'Document Delete'),
        ('barcode_assign', 'Barcode Assignment'),
        ('admin_action', 'Admin Action'),
    ]
    
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    action = models.CharField(max_length=20, choices=ACTION_CHOICES)
    description = models.CharField(max_length=500)
    ip_address = models.GenericIPAddressField(null=True, blank=True)
    timestamp = models.DateTimeField(auto_now_add=True)
    document = models.ForeignKey(Document, on_delete=models.SET_NULL, null=True, blank=True)

    class Meta:
        ordering = ['-timestamp']

    def __str__(self):
        return f"{self.user.username} - {self.get_action_display()} - {self.timestamp}"
