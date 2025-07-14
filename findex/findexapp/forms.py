from django import forms
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm
from django.core.exceptions import ValidationError
from .models import Document, Project, DocType, ExportControl, User, BarcodeRange, SystemSettings


class LoginForm(AuthenticationForm):
    """Custom login form with modern styling"""
    username = forms.CharField(
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'Benutzername',
            'autofocus': True
        })
    )
    password = forms.CharField(
        widget=forms.PasswordInput(attrs={
            'class': 'form-control',
            'placeholder': 'Passwort'
        })
    )


class DocumentUploadForm(forms.ModelForm):
    """Form for uploading new documents"""
    
    class Meta:
        model = Document
        fields = [
            'doc_type', 'barcode_number', 'document_file', 'version', 
            'output_date', 'title_de', 'title_en', 'title_fr', 
            'project', 'export_control', 'is_active', 'description'
        ]
        widgets = {
            'doc_type': forms.Select(attrs={'class': 'form-select'}),
            'barcode_number': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'Barcode-Nummer (optional)'
            }),
            'document_file': forms.FileInput(attrs={
                'class': 'form-control',
                'accept': '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.bmp,.tiff,.tif'
            }),
            'version': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'Version (z.B. 1.0, Rev A)'
            }),
            'output_date': forms.DateInput(attrs={
                'class': 'form-control',
                'type': 'date'
            }),
            'title_de': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'Titel (Deutsch)'
            }),
            'title_en': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'Title (English)'
            }),
            'title_fr': forms.TextInput(attrs={
                'class': 'form-control',
                'placeholder': 'Titre (Français)'
            }),
            'project': forms.Select(attrs={'class': 'form-select'}),
            'export_control': forms.Select(attrs={'class': 'form-select'}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
            'description': forms.Textarea(attrs={
                'class': 'form-control',
                'rows': 3,
                'placeholder': 'Beschreibung / Bemerkung'
            }),
        }

    def clean(self):
        cleaned_data = super().clean()
        title_de = cleaned_data.get('title_de')
        title_en = cleaned_data.get('title_en')
        title_fr = cleaned_data.get('title_fr')

        # At least one title must be provided
        if not any([title_de, title_en, title_fr]):
            raise ValidationError("Mindestens ein Titel (DE, EN oder FR) muss eingegeben werden.")

        return cleaned_data


class DocumentEditForm(DocumentUploadForm):
    """Form for editing existing documents (without file field)"""
    
    class Meta(DocumentUploadForm.Meta):
        exclude = ['document_file', 'uploaded_by', 'uploaded_at']


class DocumentSearchForm(forms.Form):
    """Advanced search form for documents"""
    
    search_query = forms.CharField(
        required=False,
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'Suche in Titel, Beschreibung, Barcode...'
        })
    )
    
    doc_type = forms.ModelChoiceField(
        queryset=DocType.objects.filter(is_active=True),
        required=False,
        empty_label="Alle Dokumenttypen",
        widget=forms.Select(attrs={'class': 'form-select'})
    )
    
    project = forms.ModelChoiceField(
        queryset=Project.objects.filter(is_active=True),
        required=False,
        empty_label="Alle Projekte",
        widget=forms.Select(attrs={'class': 'form-select'})
    )
    
    export_control = forms.ModelChoiceField(
        queryset=ExportControl.objects.filter(is_active=True),
        required=False,
        empty_label="Alle Export Controls",
        widget=forms.Select(attrs={'class': 'form-select'})
    )
    
    is_active = forms.ChoiceField(
        choices=[('', 'Alle'), ('true', 'Aktiv'), ('false', 'Inaktiv')],
        required=False,
        widget=forms.Select(attrs={'class': 'form-select'})
    )
    
    date_from = forms.DateField(
        required=False,
        widget=forms.DateInput(attrs={
            'class': 'form-control',
            'type': 'date'
        })
    )
    
    date_to = forms.DateField(
        required=False,
        widget=forms.DateInput(attrs={
            'class': 'form-control',
            'type': 'date'
        })
    )


class UserForm(forms.ModelForm):
    """Form for creating and editing users"""
    
    password = forms.CharField(
        widget=forms.PasswordInput(attrs={'class': 'form-control'}),
        required=False,
        help_text="Leer lassen, um Passwort nicht zu ändern"
    )
    
    class Meta:
        model = User
        fields = ['username', 'email', 'first_name', 'last_name', 'role', 'is_active', 'must_change_password']
        widgets = {
            'username': forms.TextInput(attrs={'class': 'form-control'}),
            'email': forms.EmailInput(attrs={'class': 'form-control'}),
            'first_name': forms.TextInput(attrs={'class': 'form-control'}),
            'last_name': forms.TextInput(attrs={'class': 'form-control'}),
            'role': forms.Select(attrs={'class': 'form-select'}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
            'must_change_password': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }


class ProjectForm(forms.ModelForm):
    """Form for managing projects"""
    
    class Meta:
        model = Project
        fields = ['name', 'description', 'is_active']
        widgets = {
            'name': forms.TextInput(attrs={'class': 'form-control'}),
            'description': forms.Textarea(attrs={'class': 'form-control', 'rows': 3}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }


class DocTypeForm(forms.ModelForm):
    """Form for managing document types"""
    
    class Meta:
        model = DocType
        fields = ['name', 'description', 'is_active']
        widgets = {
            'name': forms.TextInput(attrs={'class': 'form-control'}),
            'description': forms.Textarea(attrs={'class': 'form-control', 'rows': 3}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }


class ExportControlForm(forms.ModelForm):
    """Form for managing export control classifications"""
    
    class Meta:
        model = ExportControl
        fields = ['name', 'code', 'description', 'is_active']
        widgets = {
            'name': forms.TextInput(attrs={'class': 'form-control'}),
            'code': forms.TextInput(attrs={'class': 'form-control'}),
            'description': forms.Textarea(attrs={'class': 'form-control', 'rows': 3}),
            'is_active': forms.CheckboxInput(attrs={'class': 'form-check-input'}),
        }


class BarcodeRangeForm(forms.ModelForm):
    """Form for creating barcode ranges"""
    
    class Meta:
        model = BarcodeRange
        fields = ['prefix', 'start_number', 'end_number']
        widgets = {
            'prefix': forms.TextInput(attrs={'class': 'form-control'}),
            'start_number': forms.NumberInput(attrs={'class': 'form-control'}),
            'end_number': forms.NumberInput(attrs={'class': 'form-control'}),
        }

    def clean(self):
        cleaned_data = super().clean()
        start_number = cleaned_data.get('start_number')
        end_number = cleaned_data.get('end_number')

        if start_number and end_number and start_number >= end_number:
            raise ValidationError("End-Nummer muss größer als Start-Nummer sein.")

        return cleaned_data


class BarcodeAssignmentForm(forms.Form):
    """Form for assigning barcodes"""
    
    quantity = forms.IntegerField(
        min_value=1,
        max_value=100,
        widget=forms.NumberInput(attrs={
            'class': 'form-control',
            'placeholder': 'Anzahl Barcodes'
        })
    )
    
    purpose = forms.CharField(
        widget=forms.TextInput(attrs={
            'class': 'form-control',
            'placeholder': 'Verwendungszweck'
        })
    )
    
    notes = forms.CharField(
        required=False,
        widget=forms.Textarea(attrs={
            'class': 'form-control',
            'rows': 3,
            'placeholder': 'Bemerkungen (optional)'
        })
    )


class PasswordChangeForm(forms.Form):
    """Form for changing user passwords"""
    
    current_password = forms.CharField(
        widget=forms.PasswordInput(attrs={
            'class': 'form-control',
            'placeholder': 'Aktuelles Passwort'
        })
    )
    
    new_password = forms.CharField(
        widget=forms.PasswordInput(attrs={
            'class': 'form-control',
            'placeholder': 'Neues Passwort'
        })
    )
    
    confirm_password = forms.CharField(
        widget=forms.PasswordInput(attrs={
            'class': 'form-control',
            'placeholder': 'Neues Passwort bestätigen'
        })
    )

    def clean(self):
        cleaned_data = super().clean()
        new_password = cleaned_data.get('new_password')
        confirm_password = cleaned_data.get('confirm_password')

        if new_password != confirm_password:
            raise ValidationError("Die Passwörter stimmen nicht überein.")

        return cleaned_data


class BatchEditForm(forms.Form):
    """Form for batch editing documents"""
    
    FIELD_CHOICES = [
        ('project', 'Projekt'),
        ('doc_type', 'Dokumenttyp'),
        ('export_control', 'Export Control'),
        ('is_active', 'Status'),
    ]
    
    field = forms.ChoiceField(
        choices=FIELD_CHOICES,
        widget=forms.Select(attrs={'class': 'form-select'})
    )
    
    project = forms.ModelChoiceField(
        queryset=Project.objects.filter(is_active=True),
        required=False,
        widget=forms.Select(attrs={'class': 'form-select'})
    )
    
    doc_type = forms.ModelChoiceField(
        queryset=DocType.objects.filter(is_active=True),
        required=False,
        widget=forms.Select(attrs={'class': 'form-select'})
    )
    
    export_control = forms.ModelChoiceField(
        queryset=ExportControl.objects.filter(is_active=True),
        required=False,
        widget=forms.Select(attrs={'class': 'form-select'})
    )
    
    is_active = forms.BooleanField(
        required=False,
        widget=forms.CheckboxInput(attrs={'class': 'form-check-input'})
    ) 