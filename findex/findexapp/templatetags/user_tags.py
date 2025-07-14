from django import template

register = template.Library()

@register.filter
def has_role(user, roles):
    """
    Check if user has any of the specified roles.
    Usage: {% if user|has_role:"editor,full_control,admin" %}
    """
    if not user or not user.is_authenticated:
        return False
    
    role_list = [role.strip() for role in roles.split(',')]
    return user.role in role_list

@register.filter
def can_edit(user):
    """Check if user can edit documents"""
    return user.is_authenticated and user.role in ['editor', 'full_control', 'admin']

@register.filter 
def can_admin(user):
    """Check if user has admin privileges"""
    return user.is_authenticated and user.role == 'admin'

@register.filter
def can_full_control(user):
    """Check if user has full control or admin privileges"""
    return user.is_authenticated and user.role in ['full_control', 'admin'] 