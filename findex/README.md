# FINDEX - Content Service Platform

Ein modernes Dokumentenverwaltungssystem basierend auf Django mit umfassenden Funktionen für Dokumentenmanagement, Benutzerrollen und Barcode-Verwaltung.

![FINDEX Logo](static/img/logo.png)

## 🌟 Features

### 📁 Dokumentenverwaltung
- **Multi-Format Support**: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, Bildformate
- **Drag & Drop Upload**: Intuitive Datei-Upload-Oberfläche
- **Mehrsprachige Titel**: Deutsch, Englisch, Französisch
- **Versionsverwaltung**: Dokumentversionen verfolgen
- **Metadaten**: Umfangreiche Dokumenteigenschaften
- **Volltext-Suche**: Erweiterte Suchfunktionen
- **Batch-Operationen**: Massenbearbeitung und -download

### 👥 Benutzerrollen & Sicherheit
- **Viewer**: Nur Lesezugriff
- **Editor**: Dokumente bearbeiten und hochladen
- **Full Control**: Erweiterte Funktionen inkl. Barcode-Verwaltung
- **Admin**: Vollzugriff auf alle Systemfunktionen

### 🏷️ Barcode-System
- **Barcode-Generierung**: Automatische Vergabe von Barcodes
- **Bereichsverwaltung**: Definierbare Barcode-Bereiche
- **Protokollierung**: Vollständige Nachverfolgung
- **Druckvorlagen**: PDF-Generierung für Barcode-Etiketten

### 🗂️ Projektverwaltung
- **Projektbasierte Organisation**: Dokumente nach Projekten sortieren
- **Export Controls**: Sicherheitsklassifizierungen
- **Dokumenttypen**: Konfigurierbare Kategorien
- **Navigation**: Intuitive Seitenleiste mit Projektübersicht

### 📊 Dashboard & Analytics
- **Übersichtsdashboard**: Wichtige Kennzahlen auf einen Blick
- **Recent Uploads**: Zuletzt hochgeladene Dokumente
- **Statistiken**: Umfassende Systemstatistiken
- **Aktivitätsverlauf**: Benutzeraktivitäten verfolgen

## 🚀 Installation & Setup

### Voraussetzungen
- Python 3.8+
- Django 5.1+
- Git

### 1. Repository klonen
```bash
git clone <repository-url>
cd findex
```

### 2. Virtual Environment erstellen
```bash
python -m venv venv
venv\Scripts\activate  # Windows
# source venv/bin/activate  # Linux/Mac
```

### 3. Dependencies installieren
```bash
pip install django pillow
```

### 4. Datenbank migrieren
```bash
cd findex
python manage.py migrate
```

### 5. Demo-Daten erstellen
```bash
python manage.py setup_demo_data
```

### 6. Server starten
```bash
python manage.py runserver
```

## 🔐 Standard-Anmeldedaten

Nach der Demo-Daten-Erstellung stehen folgende Benutzer zur Verfügung:

| Rolle | Benutzername | Passwort | Beschreibung |
|-------|-------------|----------|--------------|
| Admin | `admin` | `admin123` | Vollzugriff |
| Full Control | `m.braeuer` | `temp123` | Marion Bräuer |
| Full Control | `s.tschorn` | `temp123` | Stefan Tschorn |
| Editor | `a.stock` | `temp123` | Andreas Stock |
| Viewer | `viewer1` | `temp123` | Test Viewer |

## 🎯 Hauptfunktionen im Detail

### 📤 Dokument-Upload (3-Schritt-Prozess)

1. **Datei auswählen**: Drag & Drop oder Dateiauswahl
2. **Attribute eingeben**: 
   - Pflichtfelder: Dokumenttyp, Version, Projekt, Export Control
   - Titel in mindestens einer Sprache (DE/EN/FR)
   - Optionale Felder: Barcode, Ausgabedatum, Beschreibung
3. **Bestätigung**: Überprüfung vor Upload

### 🔍 Erweiterte Suche

- **Schnellsuche**: Volltextsuche über alle Dokumentfelder
- **Filter**: Nach Projekt, Dokumenttyp, Export Control, Status
- **Datumsbereich**: Zeitraum-basierte Suche
- **Batch-Aktionen**: Massenoperationen auf Suchergebnisse

### 🏷️ Barcode-Verwaltung

- **Bereiche definieren**: Präfix + Nummernbereich
- **Automatische Vergabe**: Fortlaufende Nummerierung
- **Protokollierung**: Wer, wann, wofür
- **PDF-Export**: Druckvorlagen für Etiketten

### 📊 Admin-Bereich

- **Benutzerverwaltung**: Anlegen, bearbeiten, Rollen zuweisen
- **System-Einstellungen**: Konfigurierbare Parameter
- **Projektverwaltung**: Projekte und Kategorien verwalten
- **Statistiken**: Detaillierte Nutzungsauswertungen

## 🗂️ Verzeichnisstruktur

```
findex/
├── findex/                    # Django-Projekt
│   ├── settings.py           # Konfiguration
│   ├── urls.py              # URL-Routing
│   └── wsgi.py              # WSGI-Konfiguration
├── findexapp/               # Haupt-App
│   ├── models.py            # Datenmodelle
│   ├── views.py             # Views/Controller
│   ├── forms.py             # Django Forms
│   ├── admin.py             # Admin-Interface
│   └── management/          # Management-Commands
├── templates/               # HTML-Templates
│   ├── base.html           # Basis-Template
│   └── findexapp/          # App-spezifische Templates
├── static/                  # Statische Dateien
│   ├── css/                # CSS-Dateien
│   ├── js/                 # JavaScript
│   └── img/                # Bilder
└── media/                   # Hochgeladene Dateien
```

## 🎨 Technologie-Stack

- **Backend**: Django 5.1, Python 3.8+
- **Frontend**: Bootstrap 5, jQuery, Font Awesome
- **Datenbank**: SQLite (Standard), PostgreSQL/MySQL möglich
- **Upload**: Django File Handling
- **Authentication**: Django Auth mit Custom User Model

## 📱 Responsive Design

Das Interface ist vollständig responsiv und funktioniert auf:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

## 🔧 Konfiguration

### System-Einstellungen
Über Django Admin oder SystemSettings Model:

- `recent_upload_days`: Anzahl Tage für "Zuletzt hochgeladen"
- `barcode_language`: Standard-Sprache für Barcodes
- `barcode_module_enabled`: Barcode-Modul aktivieren
- `max_file_size_mb`: Maximale Dateigröße in MB

### Dateigrößen-Limits
In `settings.py`:
```python
FILE_UPLOAD_MAX_MEMORY_SIZE = 10485760  # 10MB
DATA_UPLOAD_MAX_MEMORY_SIZE = 10485760  # 10MB
```

## 🔄 API-Endpunkte

- `/api/document-stats/`: Dokumentstatistiken (JSON)
- `/api/batch-edit/`: Batch-Bearbeitung (POST)

## 🛡️ Sicherheit

- **CSRF-Schutz**: Eingebaut in alle Forms
- **Authentifizierung**: Erforderlich für alle Funktionen
- **Autorisierung**: Rollenbasierte Zugriffskontrolle
- **File Validation**: Überprüfung von Dateitypen
- **SQL Injection**: Schutz durch Django ORM

## 📝 Entwicklung

### Neue Features hinzufügen
1. Model in `models.py` erweitern
2. Migration erstellen: `python manage.py makemigrations`
3. Migration anwenden: `python manage.py migrate`
4. Views in `views.py` implementieren
5. Templates erstellen
6. URLs in `urls.py` registrieren

### Debugging
- Debug-Modus in `settings.py`: `DEBUG = True`
- Django Admin: `/django-admin/`
- Logs in Console bei Entwicklungsserver

## 🚢 Deployment

Für Produktionsumgebung:
1. `DEBUG = False` in settings.py
2. `ALLOWED_HOSTS` konfigurieren
3. Statische Dateien sammeln: `python manage.py collectstatic`
4. Web-Server (nginx, Apache) konfigurieren
5. WSGI-Server (gunicorn, uWSGI) verwenden

## 📞 Support

Bei Fragen oder Problemen:
- Django Dokumentation: https://docs.djangoproject.com/
- Bootstrap Dokumentation: https://getbootstrap.com/docs/
- Font Awesome Icons: https://fontawesome.com/

## 📄 Lizenz

Dieses Projekt ist für interne Nutzung entwickelt.

---

**FINDEX Content Service Platform** - Effizientes Dokumentenmanagement für moderne Arbeitsplätze. 