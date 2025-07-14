from django.core.management.base import BaseCommand
from django.contrib.auth import get_user_model
from findexapp.models import Project, DocType, ExportControl, SystemSettings, BarcodeRange

User = get_user_model()


class Command(BaseCommand):
    help = 'Set up demo data for FINDEX Content Service Platform'

    def add_arguments(self, parser):
        parser.add_argument(
            '--reset',
            action='store_true',
            help='Reset all data before creating demo data',
        )

    def handle(self, *args, **options):
        if options['reset']:
            self.stdout.write('Resetting existing data...')
            User.objects.filter(is_superuser=False).delete()
            Project.objects.all().delete()
            DocType.objects.all().delete()
            ExportControl.objects.all().delete()
            BarcodeRange.objects.all().delete()
            
        self.stdout.write('Creating demo data for FINDEX...')
        
        # Create admin user if not exists
        admin_user, created = User.objects.get_or_create(
            username='admin',
            defaults={
                'email': 'admin@findex.local',
                'first_name': 'System',
                'last_name': 'Administrator',
                'role': 'admin',
                'is_staff': True,
                'is_superuser': True,
                'must_change_password': False,
            }
        )
        if created:
            admin_user.set_password('admin123')
            admin_user.save()
            self.stdout.write(f'✓ Created admin user: {admin_user.username}')
        else:
            self.stdout.write(f'✓ Admin user already exists: {admin_user.username}')

        # Create sample users
        users_data = [
            {
                'username': 'm.braeuer',
                'email': 'marion.braeuer@rolls-royce.com',
                'first_name': 'Marion',
                'last_name': 'Bräuer',
                'role': 'full_control',
                'password': 'temp123'
            },
            {
                'username': 's.tschorn',
                'email': 'stefan.tschorn@rolls-royce.com', 
                'first_name': 'Stefan',
                'last_name': 'Tschorn',
                'role': 'full_control',
                'password': 'temp123'
            },
            {
                'username': 'a.stock',
                'email': 'andreas.stock@rolls-royce.com',
                'first_name': 'Andreas', 
                'last_name': 'Stock',
                'role': 'editor',
                'password': 'temp123'
            },
            {
                'username': 'viewer1',
                'email': 'viewer@findex.local',
                'first_name': 'Test',
                'last_name': 'Viewer',
                'role': 'viewer',
                'password': 'temp123'
            }
        ]

        for user_data in users_data:
            user, created = User.objects.get_or_create(
                username=user_data['username'],
                defaults={
                    'email': user_data['email'],
                    'first_name': user_data['first_name'],
                    'last_name': user_data['last_name'],
                    'role': user_data['role'],
                    'is_active': True,
                    'must_change_password': True,
                }
            )
            if created:
                user.set_password(user_data['password'])
                user.save()
                self.stdout.write(f'✓ Created user: {user.username} ({user.get_role_display()})')

        # Create Projects based on the document types mentioned
        projects_data = [
            {'name': 'WI.CR.0381', 'description': 'Allgemeine Preisanpassung bei Ersatzteilen'},
            {'name': 'WI.EP.0031', 'description': 'Planung der Fertigungsabläufe für Spezialverfahren'},
            {'name': 'WI.EP.0050', 'description': 'Prüfplanung für die Serienfertigung am Standort Oberursel'},
            {'name': 'WI.EP.0067', 'description': 'Großplanung Fertigung am Standort Oberursel'},
            {'name': 'WI.EP.0110', 'description': 'Standardisierungsberichte'},
            {'name': 'WI.EP.0117', 'description': 'Serialisierung von Bauteilen am Standort Oberursel'},
            {'name': 'TBD', 'description': 'To Be Defined - Allgemeine Dokumente'},
        ]

        for project_data in projects_data:
            project, created = Project.objects.get_or_create(
                name=project_data['name'],
                defaults={
                    'description': project_data['description'],
                    'is_active': True,
                    'created_by': admin_user,
                }
            )
            if created:
                self.stdout.write(f'✓ Created project: {project.name}')

        # Create Document Types
        doc_types_data = [
            {'name': 'CR', 'description': 'Change Request'},
            {'name': 'WI', 'description': 'Work Instruction'},
            {'name': 'SOP', 'description': 'Standard Operating Procedure'},
            {'name': 'Manual', 'description': 'Manual/Handbuch'},
            {'name': 'Report', 'description': 'Bericht'},
            {'name': 'Drawing', 'description': 'Zeichnung'},
            {'name': 'Specification', 'description': 'Spezifikation'},
        ]

        for doc_type_data in doc_types_data:
            doc_type, created = DocType.objects.get_or_create(
                name=doc_type_data['name'],
                defaults={
                    'description': doc_type_data['description'],
                    'is_active': True,
                }
            )
            if created:
                self.stdout.write(f'✓ Created document type: {doc_type.name}')

        # Create Export Control Classifications
        export_controls_data = [
            {'code': 'C4.13-1 CA', 'name': 'Export Control Level 1'},
            {'code': 'C3.1-1 CA', 'name': 'Export Control Level 2'},
            {'code': 'C3.2-3 CA', 'name': 'Export Control Level 3'},
            {'code': 'C3.4-13 CA', 'name': 'Export Control Level 4'},
            {'code': 'C3.1-3 CA', 'name': 'Export Control Level 5'},
            {'code': 'GP CR 3.1', 'name': 'General Purpose Level 1'},
            {'code': 'GP EP 3.2.3', 'name': 'General Purpose Level 2'},
            {'code': 'GP EP 2.4', 'name': 'General Purpose Level 3'},
            {'code': 'GP EP 1.3', 'name': 'General Purpose Level 4'},
        ]

        for ec_data in export_controls_data:
            export_control, created = ExportControl.objects.get_or_create(
                code=ec_data['code'],
                defaults={
                    'name': ec_data['name'],
                    'description': f'Export control classification {ec_data["code"]}',
                    'is_active': True,
                }
            )
            if created:
                self.stdout.write(f'✓ Created export control: {export_control.code}')

        # Create System Settings
        settings_data = [
            {
                'key': 'recent_upload_days',
                'value': '7',
                'description': 'Number of days for recent uploads display'
            },
            {
                'key': 'barcode_language',
                'value': 'de',
                'description': 'Default language for barcode generation'
            },
            {
                'key': 'barcode_module_enabled',
                'value': 'true',
                'description': 'Enable/disable barcode module functionality'
            },
            {
                'key': 'max_file_size_mb',
                'value': '100',
                'description': 'Maximum file size for uploads in MB'
            },
        ]

        for setting_data in settings_data:
            setting, created = SystemSettings.objects.get_or_create(
                key=setting_data['key'],
                defaults={
                    'value': setting_data['value'],
                    'description': setting_data['description'],
                    'updated_by': admin_user,
                }
            )
            if created:
                self.stdout.write(f'✓ Created system setting: {setting.key}')

        # Create Barcode Ranges
        barcode_ranges_data = [
            {'prefix': '5099', 'start': 240000, 'end': 250000},
            {'prefix': '2025', 'start': 1, 'end': 10000},
            {'prefix': 'WI', 'start': 1000, 'end': 9999},
            {'prefix': 'CR', 'start': 1000, 'end': 9999},
        ]

        for br_data in barcode_ranges_data:
            barcode_range, created = BarcodeRange.objects.get_or_create(
                prefix=br_data['prefix'],
                start_number=br_data['start'],
                defaults={
                    'end_number': br_data['end'],
                    'current_number': br_data['start'],
                    'is_active': True,
                    'created_by': admin_user,
                }
            )
            if created:
                self.stdout.write(f'✓ Created barcode range: {barcode_range}')

        self.stdout.write(
            self.style.SUCCESS('\n✅ Demo data setup completed successfully!')
        )
        
        self.stdout.write('\n📋 Login Information:')
        self.stdout.write('👤 Admin: admin / admin123')
        self.stdout.write('👤 Full Control: m.braeuer / temp123')
        self.stdout.write('👤 Full Control: s.tschorn / temp123')
        self.stdout.write('👤 Editor: a.stock / temp123')
        self.stdout.write('👤 Viewer: viewer1 / temp123')
        
        self.stdout.write('\n🚀 You can now start the development server with:')
        self.stdout.write('python manage.py runserver')
        self.stdout.write('\n🌐 Access the application at: http://127.0.0.1:8000/')
        self.stdout.write('🔧 Django Admin at: http://127.0.0.1:8000/django-admin/') 