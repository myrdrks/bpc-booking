<?php
/**
 * Admin: E-Mail Templates Verwaltung
 */
require_once __DIR__ . '/config.php';

// Admin-Authentifizierung prüfen
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin.php');
    exit;
}

$db = Database::getInstance();

// Template speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Ungültiger CSRF-Token');
    }
    
    $templateId = $_POST['template_id'];
    $subject = $_POST['subject'];
    $bodyHtml = $_POST['body_html'];
    
    try {
        $db->query(
            "UPDATE email_templates SET subject = ?, body_html = ?, updated_at = NOW() WHERE id = ?",
            [$subject, $bodyHtml, $templateId]
        );
        $_SESSION['success_message'] = 'Template erfolgreich gespeichert!';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Fehler beim Speichern: ' . $e->getMessage();
    }
    
    header('Location: admin-email-templates.php');
    exit;
}

// Template auf Standard zurücksetzen
if (isset($_GET['reset']) && isset($_GET['id'])) {
    if (!validateCsrfToken($_GET['token'] ?? '')) {
        die('Ungültiger CSRF-Token');
    }
    
    // Hier müsste die Original-Version aus der Migration geladen werden
    $_SESSION['info_message'] = 'Reset-Funktion: Bitte Template manuell wiederherstellen oder Migration erneut ausführen.';
    header('Location: admin-email-templates.php');
    exit;
}

// Alle Templates laden
$templates = $db->fetchAll("SELECT * FROM email_templates ORDER BY name ASC");

// Admin Layout verwenden
if (!empty($successMessage)) {
    $_SESSION['success_message'] = $successMessage;
}
if (!empty($errorMessage)) {
    $_SESSION['error_message'] = $errorMessage;
}

require_once __DIR__ . '/admin-header.php';
renderAdminHeader('E-Mail Templates', 'templates');
?>
    <link rel="stylesheet" href="assets/css/booking.css">
    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
    <style>
        .templates-grid {
            display: grid;
            gap: 2rem;
            margin: 2rem 0;
        }
        .template-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .template-card h3 {
            margin: 0 0 1rem 0;
            color: #333F48;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .template-description {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333F48;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        textarea.form-control {
            min-height: 300px;
            font-size: 0.9em;
        }
        .CodeMirror {
            border: 1px solid #ddd;
            border-radius: 4px;
            height: auto;
            min-height: 300px;
            font-size: 14px;
        }
        .variables-info {
            background: #fff5f0;
            border-left: 4px solid #E35205;
            padding: 1rem;
            margin: 1rem 0;
        }
        .variables-info h4 {
            margin: 0 0 0.5rem 0;
            color: #E35205;
        }
        .variable-tag {
            display: inline-block;
            background: #333F48;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            margin: 0.25rem;
            cursor: pointer;
        }
        .variable-tag:hover {
            background: #E35205;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .preview-button {
            background: #9b59b6;
            color: white;
        }
        .preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            overflow-y: auto;
        }
        .preview-content {
            background: white;
            max-width: 800px;
            margin: 50px auto;
            padding: 2rem;
            border-radius: 8px;
            position: relative;
        }
        .preview-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .preview-iframe {
            width: 100%;
            min-height: 500px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 1rem;
        }
        .template-card.collapsed .template-form {
            display: none;
        }
        .template-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: background 0.2s;
        }
        .template-header:hover {
            background: #e9ecef;
        }
        .template-header h3 {
            margin: 0;
            color: #333F48;
        }
        .template-toggle {
            font-size: 1.5em;
            color: #6c757d;
            transition: transform 0.2s;
        }
        .template-card.collapsed .template-toggle {
            transform: rotate(-90deg);
        }
        .template-form {
            padding: 0 1rem 1rem 1rem;
        }
    </style>
    
    <div style="max-width: 1200px; margin: 0 auto;">
        
        <div style="margin-bottom: 1rem;">
            <button onclick="toggleAllTemplates()" class="btn btn-secondary">📋 Alle auf-/zuklappen</button>
        </div>
        
        <div class="templates-grid">
            <?php foreach ($templates as $template): ?>
                <?php 
                $variables = json_decode($template['available_variables'], true) ?? [];
                ?>
                <div class="template-card collapsed" id="card-<?= $template['id'] ?>">
                    <div class="template-header" onclick="toggleTemplate(<?= $template['id'] ?>)">
                        <div>
                            <h3>
                                <?= h($template['name']) ?>
                                <span style="font-size: 0.8em; color: #6c757d;">
                                    (<?= h($template['template_key']) ?>)
                                </span>
                            </h3>
                            <?php if ($template['description']): ?>
                                <div style="font-size: 0.9em; color: #6c757d; margin-top: 0.25rem;">
                                    <?= h($template['description']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="template-toggle">▼</span>
                    </div>
                    
                    <div class="template-form">
                        <form method="POST" id="form-<?= $template['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                        <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                        
                        <div class="form-group">
                            <label for="subject-<?= $template['id'] ?>">Betreffzeile:</label>
                            <input type="text" 
                                   id="subject-<?= $template['id'] ?>" 
                                   name="subject" 
                                   class="form-control" 
                                   value="<?= h($template['subject']) ?>" 
                                   required>
                        </div>
                        
                        <div class="variables-info">
                            <h4>Verfügbare Variablen:</h4>
                            <p style="margin: 0.5rem 0; font-size: 0.9em; color: #6c757d;">
                                Klicken Sie auf eine Variable, um sie zu kopieren:
                            </p>
                            <?php foreach ($variables as $var => $description): ?>
                                <span class="variable-tag" 
                                      onclick="copyVariable('{{<?= $var ?>}}', this)"
                                      title="<?= h($description) ?>">
                                    {{<?= $var ?>}}
                                </span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="body-<?= $template['id'] ?>">HTML-Template:</label>
                            <textarea id="body-<?= $template['id'] ?>" 
                                      name="body_html" 
                                      class="form-control" 
                                      required><?= h($template['body_html']) ?></textarea>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" name="save_template" class="btn btn-primary">
                                💾 Speichern
                            </button>
                            <button type="button" 
                                    class="btn preview-button" 
                                    onclick="previewTemplate(<?= $template['id'] ?>)">
                                👁️ Vorschau
                            </button>
                            <a href="?reset=1&id=<?= $template['id'] ?>&token=<?= getCsrfToken() ?>" 
                               class="btn btn-secondary"
                               onclick="return confirm('Template wirklich zurücksetzen?')">
                                ↻ Zurücksetzen
                            </a>
                        </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <div class="preview-content">
            <button class="preview-close" onclick="closePreview()">✕ Schließen</button>
            <h2>E-Mail Vorschau</h2>
            <div id="previewSubject" style="font-weight: bold; padding: 1rem; background: #f8f9fa; border-radius: 4px; margin: 1rem 0;"></div>
            <iframe id="previewFrame" class="preview-iframe"></iframe>
        </div>
    </div>
    
    <script>
        function toggleTemplate(templateId) {
            const card = document.getElementById('card-' + templateId);
            card.classList.toggle('collapsed');
        }
        
        function toggleAllTemplates() {
            const cards = document.querySelectorAll('.template-card');
            const firstCard = cards[0];
            const shouldCollapse = !firstCard.classList.contains('collapsed');
            
            cards.forEach(card => {
                if (shouldCollapse) {
                    card.classList.add('collapsed');
                } else {
                    card.classList.remove('collapsed');
                }
            });
        }
        
        function copyVariable(varName, element) {
            navigator.clipboard.writeText(varName).then(() => {
                const original = element.textContent;
                element.textContent = '✓ Kopiert!';
                element.style.background = '#28a745';
                setTimeout(() => {
                    element.textContent = original;
                    element.style.background = '#333F48';
                }, 1500);
            });
        }
        
        function previewTemplate(templateId) {
            const subject = document.getElementById('subject-' + templateId).value;
            const bodyHtml = document.getElementById('body-' + templateId).value;
            
            // Beispiel-Daten für Vorschau
            const sampleData = {
                '{{customer_name}}': 'Max Mustermann',
                '{{booking_number}}': '#000123',
                '{{room_name}}': 'CLUB27',
                '{{booking_date}}': '15.12.2025',
                '{{start_time}}': '18:00',
                '{{end_time}}': '22:00',
                '{{total_price}}': '750,00',
                '{{num_persons}}': '50',
                '{{customer_email}}': 'max.mustermann@example.com',
                '{{customer_phone}}': '+49 421 12345678',
                '{{rejection_reason}}': 'Der gewünschte Termin ist leider bereits ausgebucht.',
                '{{admin_url}}': window.location.origin + '/admin.php'
            };
            
            let previewSubject = subject;
            let previewBody = bodyHtml;
            
            // Variablen ersetzen
            for (const [key, value] of Object.entries(sampleData)) {
                previewSubject = previewSubject.replace(new RegExp(key, 'g'), value);
                previewBody = previewBody.replace(new RegExp(key, 'g'), value);
            }
            
            // Bedingte Blöcke verarbeiten (einfache Implementation)
            previewBody = previewBody.replace(/{{#if.*?}}(.*?){{\/if}}/gs, '$1');
            
            document.getElementById('previewSubject').textContent = 'Betreff: ' + previewSubject;
            
            const iframe = document.getElementById('previewFrame');
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.open();
            iframeDoc.write(previewBody);
            iframeDoc.close();
            
            document.getElementById('previewModal').style.display = 'block';
        }
        
        function closePreview() {
            document.getElementById('previewModal').style.display = 'none';
        }
        
        // ESC-Taste zum Schließen
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closePreview();
            }
        });
    </script>
    
    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
    <script>
        // CodeMirror für alle body_html Textareas initialisieren
        document.addEventListener('DOMContentLoaded', function() {
            const textareas = document.querySelectorAll('textarea[name="body_html"]');
            
            textareas.forEach(function(textarea) {
                const editor = CodeMirror.fromTextArea(textarea, {
                    mode: 'htmlmixed',
                    theme: 'monokai',
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 4,
                    tabSize: 4,
                    autoCloseTags: true,
                    matchBrackets: true,
                    viewportMargin: Infinity
                });
                
                // Höhe anpassen
                editor.setSize(null, 400);
                
                // Bei Formular-Submit CodeMirror Inhalt in Textarea übertragen
                const form = textarea.closest('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        editor.save();
                    });
                }
            });
        });
    </script>
    </div>

<?php renderAdminFooter(); ?>
