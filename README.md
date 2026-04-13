# Gesundheits-Tracker

Ein minimalistischer, webbasierter Gesundheits-Tracker zur Protokollierung von Körpergewicht und sportlichen Aktivitäten. Das Projekt nutzt PHP für das Backend, Chart.js für die Visualisierung und speichert alle Daten lokal in einer JSON-Datei – keine Datenbank (SQL) erforderlich.

## 🚀 Features

  - **Gewichtstracking:** Erfassung des täglichen Gewichts.
  - **Aktivitätsprotokoll:** Auswahl verschiedener Sportdisziplinen inklusive Intensitätsstufen.
  - **"Kein Sport"-Option:** Spezielle Logik zur Handhabung von Ruhetagen (Intensität wird automatisch auf 0 gesetzt).
  - **Datenvisualisierung:** Ein interaktives Liniendiagramm zeigt den Gewichtsverlauf über die Zeit.
  - **Datenhaltung:** Speicherung erfolgt in der `health_data.json`, was Backups und manuelle Bearbeitung extrem einfach macht.
  - **Responsive Design:** Optimierte Ansicht für Desktop und mobile Endgeräte.
  - **Löschfunktion:** Einträge können direkt über die Tabelle entfernt werden.

## 🛠️ Installation & Setup

1.  **Voraussetzungen:**

      - Ein Webserver mit PHP-Unterstützung (z. B. Apache mit XAMPP, Nginx oder ein Webhosting-Paket).
      - Schreibrechte im Projektordner (damit die `health_data.json` erstellt/beschrieben werden kann).

2.  **Dateien kopieren:**
    Lade die Dateien `health.php` und `health_data.json` auf deinen Server hoch.

3.  **Berechtigungen:**
    Stelle sicher, dass der Webserver Schreibzugriff auf die `health_data.json` hat:

    ```bash
    chmod 664 health_data.json
    ```

4.  **Aufrufen:**
    Öffne die `health.php` in deinem Browser (z. B. `http://localhost/health.php`).

## 📊 Dateistruktur

  - `health.php`: Die Hauptanwendung (Logik, UI und Diagramm).
  - `health_data.json`: Die Datenbank im JSON-Format.
  - `README.md`: Diese Dokumentation.
  - `LICENSE`: Das Projekt steht unter der **GNU Affero General Public License v3 (AGPL-3.0)**.

## 🖥️ Technologien

  - **Backend:** PHP
  - **Frontend:** HTML5, CSS3 (GitHub-Style UI)
  - **Charts:** [Chart.js](https://www.chartjs.org/)
  - **Datenformat:** JSON

## 📝 Nutzungshinweise

  - **Datum:** Standardmäßig wird das aktuelle Datum vorgeschlagen, kann aber für Nachträge geändert werden.
  - **Sport:** Wählst du "Kein Sport", wird das Feld für die Intensität ignoriert.
  - **Sortierung:** Die Daten werden automatisch nach Datum sortiert in der JSON-Datei gespeichert.

## 📄 Lizenz

Dieses Projekt ist lizenziert unter der GNU Affero General Public License v3.0 - siehe die [LICENSE](https://www.google.com/search?q=LICENSE) Datei für Details. Erstellt durch Herr-NM.

-----

*Erstellt für die private Gesundheitsverfolgung.*