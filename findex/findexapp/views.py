from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth import login, logout, authenticate
from django.contrib.auth.decorators import login_required, user_passes_test
from django.contrib import messages
from django.http import JsonResponse, HttpResponse, Http404
from django.core.paginator import Paginator
from django.db.models import Q, Count
from django.utils import timezone
from django.views.decorators.http import require_http_methods
from django.views.decorators.csrf import csrf_exempt
from django.conf import settings
import os
import json
from datetime import datetime, timedelta

from .models import (
    Document, Project, DocType, ExportControl, User, 
    BarcodeRange, BarcodeAssignment, SystemSettings, UserActivity
)
from .forms import (
    LoginForm, DocumentUploadForm, DocumentEditForm, DocumentSearchForm,
    UserForm, ProjectForm, DocTypeForm, ExportControlForm, 
    BarcodeRangeForm, BarcodeAssignmentForm, PasswordChangeForm, BatchEditForm
)


def is_admin(user):
    """Check if user is admin"""
    return user.is_authenticated and user.role == 'admin'


def is_full_control_or_admin(user):
    """Check if user has full control or admin privileges"""
    return user.is_authenticated and user.role in ['full_control', 'admin']


def is_editor_or_above(user):
    """Check if user can edit documents"""
    return user.is_authenticated and user.role in ['editor', 'full_control', 'admin']


def log_user_activity(user, action, description, ip_address=None, document=None):
    """Helper function to log user activities"""
    UserActivity.objects.create(
        user=user,
        action=action,
        description=description,
        ip_address=ip_address,
        document=document
    )


def get_client_ip(request):
    """Get client IP address"""
    x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
    if x_forwarded_for:
        ip = x_forwarded_for.split(',')[0]
    else:
        ip = request.META.get('REMOTE_ADDR')
    return ip


def user_login(request):
    """User login view"""
    if request.user.is_authenticated:
        return redirect('dashboard')
    
    if request.method == 'POST':
        form = LoginForm(request, data=request.POST)
        if form.is_valid():
            username = form.cleaned_data.get('username')
            password = form.cleaned_data.get('password')
            user = authenticate(username=username, password=password)
            if user is not None:
                login(request, user)
                log_user_activity(
                    user, 'login', f'User logged in', 
                    get_client_ip(request)
                )
                messages.success(request, f'Willkommen, {user.first_name or user.username}!')
                return redirect('dashboard')
    else:
        form = LoginForm()
    
    return render(request, 'findexapp/login.html', {'form': form})


@login_required
def user_logout(request):
    """User logout view"""
    log_user_activity(
        request.user, 'logout', f'User logged out', 
        get_client_ip(request)
    )
    logout(request)
    messages.info(request, 'Sie wurden erfolgreich abgemeldet.')
    return redirect('login')


@login_required
def dashboard(request):
    """Main dashboard view"""
    # Get statistics
    total_documents = Document.objects.count()
    active_documents = Document.objects.filter(is_active=True).count()
    total_projects = Project.objects.filter(is_active=True).count()
    
    # Recent uploads based on system setting
    recent_days = int(SystemSettings.get_setting('recent_upload_days', 7))
    recent_date = timezone.now() - timedelta(days=recent_days)
    recent_documents = Document.objects.filter(
        uploaded_at__gte=recent_date
    ).order_by('-uploaded_at')[:10]
    
    # Projects for navigation
    projects = Project.objects.filter(is_active=True).order_by('name')
    
    # Search form
    search_form = DocumentSearchForm()
    
    context = {
        'total_documents': total_documents,
        'active_documents': active_documents,
        'total_projects': total_projects,
        'recent_documents': recent_documents,
        'recent_days': recent_days,
        'projects': projects,
        'search_form': search_form,
    }
    
    return render(request, 'findexapp/dashboard.html', context)


@login_required
def document_list(request):
    """Document listing with search and filtering"""
    documents = Document.objects.select_related('project', 'doc_type', 'export_control', 'uploaded_by')
    search_form = DocumentSearchForm(request.GET)
    
    # Apply filters
    if search_form.is_valid():
        if search_form.cleaned_data['search_query']:
            query = search_form.cleaned_data['search_query']
            documents = documents.filter(
                Q(title_de__icontains=query) |
                Q(title_en__icontains=query) |
                Q(title_fr__icontains=query) |
                Q(description__icontains=query) |
                Q(barcode_number__icontains=query) |
                Q(version__icontains=query) |
                Q(document_file__icontains=query)
            )
        
        if search_form.cleaned_data['doc_type']:
            documents = documents.filter(doc_type=search_form.cleaned_data['doc_type'])
        
        if search_form.cleaned_data['project']:
            documents = documents.filter(project=search_form.cleaned_data['project'])
        
        if search_form.cleaned_data['export_control']:
            documents = documents.filter(export_control=search_form.cleaned_data['export_control'])
        
        if search_form.cleaned_data['is_active']:
            is_active = search_form.cleaned_data['is_active'] == 'true'
            documents = documents.filter(is_active=is_active)
        
        if search_form.cleaned_data['date_from']:
            documents = documents.filter(uploaded_at__gte=search_form.cleaned_data['date_from'])
        
        if search_form.cleaned_data['date_to']:
            documents = documents.filter(uploaded_at__lte=search_form.cleaned_data['date_to'])
    
    # Pagination
    paginator = Paginator(documents.order_by('-uploaded_at'), 25)
    page_number = request.GET.get('page')
    documents_page = paginator.get_page(page_number)
    
    # Additional data for template
    export_controls = ExportControl.objects.filter(is_active=True).order_by('name')
    
    context = {
        'documents': documents_page,
        'search_form': search_form,
        'export_controls': export_controls,
        'total_results': documents.count(),
    }
    
    return render(request, 'findexapp/document_list.html', context)


@login_required
@user_passes_test(is_editor_or_above)
def document_upload(request):
    """Document upload view with 2-step process"""
    if request.method == 'POST':
        # Handle AJAX upload for 2-step process
        if request.content_type.startswith('multipart/form-data'):
            form = DocumentUploadForm(request.POST, request.FILES)
            if form.is_valid():
                document = form.save(commit=False)
                document.uploaded_by = request.user
                document.save()
                
                log_user_activity(
                    request.user, 'upload', 
                    f'Uploaded document: {document.primary_title}',
                    get_client_ip(request), document
                )
                
                return JsonResponse({
                    'success': True,
                    'message': f'Dokument "{document.primary_title}" erfolgreich hochgeladen!',
                    'document_id': document.pk
                })
            else:
                errors = {}
                for field, field_errors in form.errors.items():
                    errors[field] = field_errors
                return JsonResponse({
                    'success': False,
                    'message': 'Validation errors occurred',
                    'errors': errors
                })
    
    # GET request - show upload form
    form = DocumentUploadForm()
    return render(request, 'findexapp/document_upload.html', {'form': form})


@login_required
def document_detail(request, pk):
    """Document detail view"""
    document = get_object_or_404(Document, pk=pk)
    return render(request, 'findexapp/document_detail.html', {'document': document})


@login_required
@user_passes_test(is_editor_or_above)
def document_edit(request, pk):
    """Document edit view"""
    document = get_object_or_404(Document, pk=pk)
    
    if request.method == 'POST':
        form = DocumentEditForm(request.POST, instance=document)
        if form.is_valid():
            form.save()
            
            log_user_activity(
                request.user, 'edit', 
                f'Edited document: {document.primary_title}',
                get_client_ip(request), document
            )
            
            messages.success(request, f'Dokument "{document.primary_title}" erfolgreich aktualisiert!')
            return redirect('document_detail', pk=document.pk)
    else:
        form = DocumentEditForm(instance=document)
    
    return render(request, 'findexapp/document_edit.html', {
        'form': form, 
        'document': document
    })


@login_required
def document_download(request, pk):
    """Document download view"""
    document = get_object_or_404(Document, pk=pk)
    
    if not document.document_file:
        raise Http404("File not found")
    
    log_user_activity(
        request.user, 'download', 
        f'Downloaded document: {document.primary_title}',
        get_client_ip(request), document
    )
    
    response = HttpResponse(
        document.document_file.read(),
        content_type='application/octet-stream'
    )
    response['Content-Disposition'] = f'attachment; filename="{os.path.basename(document.document_file.name)}"'
    return response


@login_required
@user_passes_test(is_editor_or_above)
def document_delete(request, pk):
    """Document delete view"""
    document = get_object_or_404(Document, pk=pk)
    
    if request.method == 'POST':
        title = document.primary_title
        document.delete()
        
        log_user_activity(
            request.user, 'delete', 
            f'Deleted document: {title}',
            get_client_ip(request)
        )
        
        messages.success(request, f'Dokument "{title}" erfolgreich gelöscht!')
        return redirect('document_list')
    
    return render(request, 'findexapp/document_delete.html', {'document': document})


@login_required
def project_documents(request, project_id):
    """View documents for a specific project"""
    project = get_object_or_404(Project, pk=project_id)
    documents = Document.objects.filter(project=project, is_active=True)
    
    # Pagination
    paginator = Paginator(documents.order_by('-uploaded_at'), 25)
    page_number = request.GET.get('page')
    page_obj = paginator.get_page(page_number)
    
    # Projects for navigation
    projects = Project.objects.filter(is_active=True).order_by('name')
    
    context = {
        'page_obj': page_obj,
        'current_project': project,
        'projects': projects,
        'total_results': documents.count(),
    }
    
    return render(request, 'findexapp/project_documents.html', context)


# Admin Views
@login_required
@user_passes_test(is_admin)
def admin_dashboard(request):
    """Admin dashboard"""
    # Statistics
    total_users = User.objects.count()
    active_users = User.objects.filter(is_active=True).count()
    total_documents = Document.objects.count()
    total_projects = Project.objects.count()
    
    # Recent activities
    recent_activities = UserActivity.objects.all()[:20]
    
    context = {
        'total_users': total_users,
        'active_users': active_users,
        'total_documents': total_documents,
        'total_projects': total_projects,
        'recent_activities': recent_activities,
    }
    
    return render(request, 'findexapp/admin/dashboard.html', context)


@login_required
@user_passes_test(is_admin)
def user_management(request):
    """User management view"""
    users = User.objects.all().order_by('username')
    
    # Pagination
    paginator = Paginator(users, 25)
    page_number = request.GET.get('page')
    page_obj = paginator.get_page(page_number)
    
    return render(request, 'findexapp/admin/user_list.html', {'page_obj': page_obj})


@login_required
@user_passes_test(is_admin)
def user_create(request):
    """Create new user"""
    if request.method == 'POST':
        form = UserForm(request.POST)
        if form.is_valid():
            user = form.save(commit=False)
            if form.cleaned_data['password']:
                user.set_password(form.cleaned_data['password'])
            else:
                user.set_password('temp123')  # Temporary password
                user.must_change_password = True
            user.save()
            
            log_user_activity(
                request.user, 'admin_action', 
                f'Created user: {user.username}',
                get_client_ip(request)
            )
            
            messages.success(request, f'Benutzer "{user.username}" erfolgreich erstellt!')
            return redirect('user_management')
    else:
        form = UserForm()
    
    return render(request, 'findexapp/admin/user_form.html', {'form': form, 'action': 'Erstellen'})


@login_required
@user_passes_test(is_admin)
def user_edit(request, pk):
    """Edit user"""
    user = get_object_or_404(User, pk=pk)
    
    if request.method == 'POST':
        form = UserForm(request.POST, instance=user)
        if form.is_valid():
            user = form.save(commit=False)
            if form.cleaned_data['password']:
                user.set_password(form.cleaned_data['password'])
            user.save()
            
            log_user_activity(
                request.user, 'admin_action', 
                f'Updated user: {user.username}',
                get_client_ip(request)
            )
            
            messages.success(request, f'Benutzer "{user.username}" erfolgreich aktualisiert!')
            return redirect('user_management')
    else:
        form = UserForm(instance=user)
    
    return render(request, 'findexapp/admin/user_form.html', {
        'form': form, 
        'user_obj': user, 
        'action': 'Bearbeiten'
    })


@login_required
def profile(request):
    """User profile view"""
    if request.method == 'POST':
        # Handle password change
        if 'change_password' in request.POST:
            form = PasswordChangeForm(request.POST)
            if form.is_valid():
                current_password = form.cleaned_data['current_password']
                if request.user.check_password(current_password):
                    request.user.set_password(form.cleaned_data['new_password'])
                    request.user.must_change_password = False
                    request.user.save()
                    
                    log_user_activity(
                        request.user, 'admin_action', 
                        'Changed password',
                        get_client_ip(request)
                    )
                    
                    messages.success(request, 'Passwort erfolgreich geändert!')
                    return redirect('profile')
                else:
                    messages.error(request, 'Aktuelles Passwort ist falsch.')
        else:
            form = PasswordChangeForm()
    else:
        form = PasswordChangeForm()
    
    # Recent user activities
    recent_activities = UserActivity.objects.filter(user=request.user)[:10]
    
    return render(request, 'findexapp/profile.html', {
        'form': form,
        'recent_activities': recent_activities
    })


# Barcode Management Views (for full_control and admin users)
@login_required
@user_passes_test(is_full_control_or_admin)
def barcode_management(request):
    """Barcode management dashboard"""
    ranges = BarcodeRange.objects.all().order_by('-created_at')
    recent_assignments = BarcodeAssignment.objects.all().order_by('-assigned_at')[:10]
    
    context = {
        'ranges': ranges,
        'recent_assignments': recent_assignments,
    }
    
    return render(request, 'findexapp/barcode/management.html', context)


@login_required
@user_passes_test(is_full_control_or_admin)
def barcode_range_create(request):
    """Create new barcode range"""
    if request.method == 'POST':
        form = BarcodeRangeForm(request.POST)
        if form.is_valid():
            barcode_range = form.save(commit=False)
            barcode_range.current_number = barcode_range.start_number
            barcode_range.created_by = request.user
            barcode_range.save()
            
            log_user_activity(
                request.user, 'admin_action', 
                f'Created barcode range: {barcode_range}',
                get_client_ip(request)
            )
            
            messages.success(request, 'Barcode-Bereich erfolgreich erstellt!')
            return redirect('barcode_management')
    else:
        form = BarcodeRangeForm()
    
    return render(request, 'findexapp/barcode/range_form.html', {'form': form})


@login_required
@user_passes_test(is_full_control_or_admin)
def barcode_assign(request):
    """Assign barcodes to user"""
    active_ranges = BarcodeRange.objects.filter(is_active=True)
    
    if request.method == 'POST':
        form = BarcodeAssignmentForm(request.POST)
        if form.is_valid():
            quantity = form.cleaned_data['quantity']
            purpose = form.cleaned_data['purpose']
            notes = form.cleaned_data['notes']
            
            # Find available range
            assigned_barcodes = []
            for barcode_range in active_ranges:
                if barcode_range.current_number + quantity <= barcode_range.end_number:
                    # Assign barcodes from this range
                    for i in range(quantity):
                        barcode_number = f"{barcode_range.prefix}{barcode_range.current_number:06d}"
                        assignment = BarcodeAssignment.objects.create(
                            barcode_number=barcode_number,
                            barcode_range=barcode_range,
                            assigned_to=request.user,
                            purpose=purpose,
                            notes=notes
                        )
                        assigned_barcodes.append(barcode_number)
                        barcode_range.current_number += 1
                    
                    barcode_range.save()
                    
                    log_user_activity(
                        request.user, 'barcode_assign', 
                        f'Assigned {quantity} barcodes for: {purpose}',
                        get_client_ip(request)
                    )
                    
                    messages.success(request, f'{quantity} Barcodes erfolgreich zugewiesen: {", ".join(assigned_barcodes)}')
                    return redirect('barcode_management')
            
            messages.error(request, 'Nicht genügend Barcodes verfügbar!')
    else:
        form = BarcodeAssignmentForm()
    
    context = {
        'form': form,
        'active_ranges': active_ranges,
    }
    
    return render(request, 'findexapp/barcode/assign.html', context)


# API Views for AJAX requests
@login_required
def api_document_stats(request):
    """API endpoint for document statistics"""
    stats = {
        'total': Document.objects.count(),
        'active': Document.objects.filter(is_active=True).count(),
        'by_project': list(Document.objects.values('project__name').annotate(count=Count('id'))),
        'by_type': list(Document.objects.values('doc_type__name').annotate(count=Count('id'))),
    }
    return JsonResponse(stats)


@login_required
@require_http_methods(["POST"])
def api_batch_edit(request):
    """API endpoint for batch editing documents"""
    if not is_editor_or_above(request.user):
        return JsonResponse({'error': 'Insufficient permissions'}, status=403)
    
    try:
        data = json.loads(request.body)
        document_ids = data.get('document_ids', [])
        field = data.get('field')
        value = data.get('value')
        
        documents = Document.objects.filter(id__in=document_ids)
        updated_count = 0
        
        for document in documents:
            if field == 'project' and value:
                project = Project.objects.get(id=value)
                document.project = project
            elif field == 'doc_type' and value:
                doc_type = DocType.objects.get(id=value)
                document.doc_type = doc_type
            elif field == 'export_control' and value:
                export_control = ExportControl.objects.get(id=value)
                document.export_control = export_control
            elif field == 'is_active':
                document.is_active = value
            
            document.save()
            updated_count += 1
        
        log_user_activity(
            request.user, 'edit', 
            f'Batch edited {updated_count} documents',
            get_client_ip(request)
        )
        
        return JsonResponse({'success': True, 'updated_count': updated_count})
    
    except Exception as e:
        return JsonResponse({'error': str(e)}, status=400)


@login_required
@require_http_methods(["POST"])
def batch_download(request):
    """Download multiple documents as ZIP"""
    import zipfile
    from io import BytesIO
    
    document_ids = request.POST.getlist('document_ids')
    if not document_ids:
        return JsonResponse({'success': False, 'message': 'No documents selected'})
    
    documents = Document.objects.filter(id__in=document_ids)
    
    # Create ZIP file in memory
    zip_buffer = BytesIO()
    with zipfile.ZipFile(zip_buffer, 'w', zipfile.ZIP_DEFLATED) as zip_file:
        for document in documents:
            if document.document_file and os.path.exists(document.document_file.path):
                # Add file to ZIP
                zip_file.write(
                    document.document_file.path,
                    f"{document.project.name}_{document.primary_title}_{document.version}.{document.file_type}"
                )
    
    zip_buffer.seek(0)
    
    # Log activity
    log_user_activity(
        request.user, 'download', 
        f'Batch downloaded {documents.count()} documents',
        get_client_ip(request)
    )
    
    response = HttpResponse(zip_buffer.getvalue(), content_type='application/zip')
    response['Content-Disposition'] = f'attachment; filename="FINDEX_Documents_{timezone.now().strftime("%Y%m%d_%H%M%S")}.zip"'
    return response


@login_required
@user_passes_test(is_editor_or_above)
@require_http_methods(["POST"])
def batch_edit(request):
    """Batch edit documents"""
    try:
        document_ids = request.POST.get('document_ids', '').split(',')
        document_ids = [id.strip() for id in document_ids if id.strip()]
        
        if not document_ids:
            return JsonResponse({'success': False, 'message': 'No documents selected'})
        
        documents = Document.objects.filter(id__in=document_ids)
        updated_count = 0
        
        # Apply updates
        if request.POST.get('project'):
            project = get_object_or_404(Project, id=request.POST.get('project'))
            documents.update(project=project)
            updated_count += 1
        
        if request.POST.get('status'):
            is_active = request.POST.get('status') == 'true'
            documents.update(is_active=is_active)
            updated_count += 1
        
        if request.POST.get('export_control'):
            export_control = get_object_or_404(ExportControl, id=request.POST.get('export_control'))
            documents.update(export_control=export_control)
            updated_count += 1
        
        # Log activity
        log_user_activity(
            request.user, 'edit', 
            f'Batch edited {documents.count()} documents',
            get_client_ip(request)
        )
        
        return JsonResponse({
            'success': True, 
            'message': f'{documents.count()} documents updated successfully'
        })
        
    except Exception as e:
        return JsonResponse({'success': False, 'message': str(e)})


@login_required
def export_documents(request):
    """Export document list as Excel"""
    try:
        import pandas as pd
        from io import BytesIO
        
        # Get all documents with related data
        documents = Document.objects.select_related(
            'project', 'doc_type', 'export_control', 'uploaded_by'
        ).all()
        
        # Prepare data for Excel
        data = []
        for doc in documents:
            data.append({
                'ID': doc.id,
                'Dokumenttyp': doc.doc_type.name,
                'Name': doc.document_file.name if doc.document_file else '',
                'Titel DE': doc.title_de,
                'Titel EN': doc.title_en,
                'Titel FR': doc.title_fr,
                'Version': doc.version,
                'Projekt': doc.project.name,
                'Export Control': doc.export_control.code,
                'Barcode': doc.barcode_number or '',
                'Ausgabedatum': doc.output_date,
                'Hochgeladen': doc.uploaded_at.strftime('%d.%m.%Y %H:%M'),
                'Hochgeladen von': doc.uploaded_by.username if doc.uploaded_by else '',
                'Status': 'Aktiv' if doc.is_active else 'Inaktiv',
                'Beschreibung': doc.description,
            })
        
        # Create DataFrame and Excel file
        df = pd.DataFrame(data)
        
        excel_buffer = BytesIO()
        with pd.ExcelWriter(excel_buffer, engine='openpyxl') as writer:
            df.to_excel(writer, sheet_name='Dokumente', index=False)
        
        excel_buffer.seek(0)
        
        # Log activity
        log_user_activity(
            request.user, 'admin_action', 
            f'Exported {len(data)} documents to Excel',
            get_client_ip(request)
        )
        
        response = HttpResponse(
            excel_buffer.getvalue(),
            content_type='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        )
        response['Content-Disposition'] = f'attachment; filename="FINDEX_Export_{timezone.now().strftime("%Y%m%d_%H%M%S")}.xlsx"'
        return response
        
    except ImportError:
        # Fallback to CSV if pandas not available
        import csv
        
        response = HttpResponse(content_type='text/csv')
        response['Content-Disposition'] = f'attachment; filename="FINDEX_Export_{timezone.now().strftime("%Y%m%d_%H%M%S")}.csv"'
        
        writer = csv.writer(response)
        writer.writerow(['ID', 'Dokumenttyp', 'Name', 'Titel DE', 'Titel EN', 'Titel FR', 'Version', 'Projekt', 'Export Control', 'Barcode', 'Status'])
        
        documents = Document.objects.select_related('project', 'doc_type', 'export_control').all()
        for doc in documents:
            writer.writerow([
                doc.id,
                doc.doc_type.name,
                doc.document_file.name if doc.document_file else '',
                doc.title_de,
                doc.title_en,
                doc.title_fr,
                doc.version,
                doc.project.name,
                doc.export_control.code,
                doc.barcode_number or '',
                'Aktiv' if doc.is_active else 'Inaktiv'
            ])
        
        return response


# Error handlers
def handler404(request, exception):
    return render(request, 'findexapp/errors/404.html', status=404)


def handler500(request):
    return render(request, 'findexapp/errors/500.html', status=500)
