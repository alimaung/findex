from django.contrib import admin
from django.contrib.auth.admin import UserAdmin as BaseUserAdmin
from .models import (
    User, Project, DocType, ExportControl, Document, 
    BarcodeRange, BarcodeAssignment, SystemSettings, UserActivity
)


@admin.register(User)
class UserAdmin(BaseUserAdmin):
    """Admin configuration for User model"""
    list_display = ('username', 'email', 'first_name', 'last_name', 'role', 'is_active', 'created_at')
    list_filter = ('role', 'is_active', 'must_change_password', 'created_at')
    search_fields = ('username', 'email', 'first_name', 'last_name')
    ordering = ('username',)
    
    fieldsets = BaseUserAdmin.fieldsets + (
        ('Custom Fields', {
            'fields': ('role', 'must_change_password')
        }),
    )
    
    add_fieldsets = BaseUserAdmin.add_fieldsets + (
        ('Custom Fields', {
            'fields': ('role', 'must_change_password')
        }),
    )


@admin.register(Project)
class ProjectAdmin(admin.ModelAdmin):
    """Admin configuration for Project model"""
    list_display = ('name', 'is_active', 'created_at', 'created_by')
    list_filter = ('is_active', 'created_at')
    search_fields = ('name', 'description')
    ordering = ('name',)
    readonly_fields = ('created_at',)


@admin.register(DocType)
class DocTypeAdmin(admin.ModelAdmin):
    """Admin configuration for DocType model"""
    list_display = ('name', 'is_active', 'created_at')
    list_filter = ('is_active', 'created_at')
    search_fields = ('name', 'description')
    ordering = ('name',)
    readonly_fields = ('created_at',)


@admin.register(ExportControl)
class ExportControlAdmin(admin.ModelAdmin):
    """Admin configuration for ExportControl model"""
    list_display = ('code', 'name', 'is_active', 'created_at')
    list_filter = ('is_active', 'created_at')
    search_fields = ('code', 'name', 'description')
    ordering = ('code',)
    readonly_fields = ('created_at',)


@admin.register(Document)
class DocumentAdmin(admin.ModelAdmin):
    """Admin configuration for Document model"""
    list_display = (
        'primary_title', 'version', 'doc_type', 'project', 
        'export_control', 'is_active', 'uploaded_at', 'uploaded_by'
    )
    list_filter = (
        'doc_type', 'project', 'export_control', 'is_active', 
        'uploaded_at', 'file_type'
    )
    search_fields = (
        'title_de', 'title_en', 'title_fr', 'description', 
        'barcode_number', 'version'
    )
    ordering = ('-uploaded_at',)
    readonly_fields = (
        'uploaded_at', 'updated_at', 'file_size', 'file_type'
    )
    
    fieldsets = (
        ('Basic Information', {
            'fields': (
                'doc_type', 'document_file', 'version', 'barcode_number'
            )
        }),
        ('Titles', {
            'fields': ('title_de', 'title_en', 'title_fr')
        }),
        ('Classification', {
            'fields': ('project', 'export_control', 'output_date')
        }),
        ('Status & Description', {
            'fields': ('is_active', 'description')
        }),
        ('Metadata', {
            'fields': (
                'uploaded_by', 'uploaded_at', 'updated_at', 
                'file_size', 'file_type'
            ),
            'classes': ('collapse',)
        }),
    )


@admin.register(BarcodeRange)
class BarcodeRangeAdmin(admin.ModelAdmin):
    """Admin configuration for BarcodeRange model"""
    list_display = (
        'prefix', 'start_number', 'end_number', 'current_number', 
        'is_active', 'is_exhausted', 'created_at', 'created_by'
    )
    list_filter = ('is_active', 'created_at')
    search_fields = ('prefix',)
    ordering = ('-created_at',)
    readonly_fields = ('created_at',)


@admin.register(BarcodeAssignment)
class BarcodeAssignmentAdmin(admin.ModelAdmin):
    """Admin configuration for BarcodeAssignment model"""
    list_display = (
        'barcode_number', 'purpose', 'assigned_to', 
        'assigned_at', 'is_used'
    )
    list_filter = ('assigned_at', 'is_used', 'barcode_range')
    search_fields = ('barcode_number', 'purpose', 'notes')
    ordering = ('-assigned_at',)
    readonly_fields = ('assigned_at',)


@admin.register(SystemSettings)
class SystemSettingsAdmin(admin.ModelAdmin):
    """Admin configuration for SystemSettings model"""
    list_display = ('key', 'value', 'description', 'updated_by', 'updated_at')
    list_filter = ('updated_at',)
    search_fields = ('key', 'description')
    ordering = ('key',)
    readonly_fields = ('updated_at',)


@admin.register(UserActivity)
class UserActivityAdmin(admin.ModelAdmin):
    """Admin configuration for UserActivity model"""
    list_display = (
        'user', 'action', 'description', 'timestamp', 'ip_address'
    )
    list_filter = ('action', 'timestamp')
    search_fields = ('user__username', 'description', 'ip_address')
    ordering = ('-timestamp',)
    readonly_fields = ('timestamp',)
    
    def has_add_permission(self, request):
        """Disable manual creation of activity logs"""
        return False
    
    def has_change_permission(self, request, obj=None):
        """Disable editing of activity logs"""
        return False
