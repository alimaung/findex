from django.urls import path
from . import views

urlpatterns = [
    # Authentication
    path('', views.user_login, name='login'),
    path('logout/', views.user_logout, name='logout'),
    
    # Main views
    path('dashboard/', views.dashboard, name='dashboard'),
    path('documents/', views.document_list, name='document_list'),
    path('documents/upload/', views.document_upload, name='document_upload'),
    path('documents/<int:pk>/', views.document_detail, name='document_detail'),
    path('documents/<int:pk>/edit/', views.document_edit, name='document_edit'),
    path('documents/<int:pk>/download/', views.document_download, name='document_download'),
    path('documents/<int:pk>/delete/', views.document_delete, name='document_delete'),
    
    # Project views
    path('projects/<int:project_id>/documents/', views.project_documents, name='project_documents'),
    
    # User profile
    path('profile/', views.profile, name='profile'),
    
    # Admin views
    path('admin/dashboard/', views.admin_dashboard, name='admin_dashboard'),
    path('admin/users/', views.user_management, name='user_management'),
    path('admin/users/create/', views.user_create, name='user_create'),
    path('admin/users/<int:pk>/edit/', views.user_edit, name='user_edit'),
    
    # Barcode management
    path('barcode/', views.barcode_management, name='barcode_management'),
    path('barcode/range/create/', views.barcode_range_create, name='barcode_range_create'),
    path('barcode/assign/', views.barcode_assign, name='barcode_assign'),
    
    # API endpoints
    path('api/document-stats/', views.api_document_stats, name='api_document_stats'),
    path('api/batch-edit/', views.api_batch_edit, name='api_batch_edit'),
    
    # Batch operations
    path('batch/download/', views.batch_download, name='batch_download'),
    path('batch/edit/', views.batch_edit, name='batch_edit'),
    path('export/documents/', views.export_documents, name='export_documents'),
] 