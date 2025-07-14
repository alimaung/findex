from django.db.models import Count
from django.utils import timezone
from datetime import timedelta
from .models import Project, Document, SystemSettings


def common_data(request):
    """
    Context processor to provide common data to all templates
    """
    if not request.user.is_authenticated:
        return {}
    
    # Get all projects for sidebar navigation (alphabetically sorted)
    all_projects = Project.objects.filter(is_active=True).annotate(
        document_count=Count('document')
    ).order_by('name')
    
    # Get statistics for header
    total_documents = Document.objects.count()
    active_documents = Document.objects.filter(is_active=True).count()
    
    # Get recent uploads for sidebar
    recent_days = int(SystemSettings.get_setting('recent_upload_days', 7))
    recent_date = timezone.now() - timedelta(days=recent_days)
    recent_uploads = Document.objects.filter(
        uploaded_at__gte=recent_date
    ).select_related('project', 'doc_type', 'uploaded_by').order_by('-uploaded_at')[:5]
    
    return {
        'all_projects': all_projects,
        'total_documents': total_documents,
        'active_documents': active_documents,
        'recent_uploads': recent_uploads,
    } 