#!/usr/bin/env python3
"""Generate GMAO Audit PDF report."""

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm, cm
from reportlab.lib.colors import HexColor, white, black
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.enums import TA_LEFT, TA_CENTER
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, HRFlowable
)
from reportlab.pdfgen import canvas

# Colors
RED_BG = HexColor('#DC2626')
RED_LIGHT = HexColor('#FEE2E2')
RED_TEXT = HexColor('#991B1B')
ORANGE_BG = HexColor('#F59E0B')
ORANGE_LIGHT = HexColor('#FEF3C7')
ORANGE_TEXT = HexColor('#92400E')
GREEN_BG = HexColor('#10B981')
GREEN_LIGHT = HexColor('#D1FAE5')
GREEN_TEXT = HexColor('#065F46')
BLUE_BG = HexColor('#3B82F6')
BLUE_LIGHT = HexColor('#DBEAFE')
BLUE_TEXT = HexColor('#1E40AF')
GRAY_BG = HexColor('#F3F4F6')
GRAY_BORDER = HexColor('#D1D5DB')
DARK_BG = HexColor('#0C1519')
PRIMARY = HexColor('#CF9D7B')
FOND_DEUX = HexColor('#162127')

# Styles
style_title = ParagraphStyle('Title', fontSize=24, fontName='Helvetica-Bold', textColor=DARK_BG, spaceAfter=6)
style_subtitle = ParagraphStyle('Subtitle', fontSize=12, fontName='Helvetica', textColor=HexColor('#6B7280'), spaceAfter=4)
style_h1 = ParagraphStyle('H1', fontSize=16, fontName='Helvetica-Bold', textColor=DARK_BG, spaceBefore=16, spaceAfter=8)
style_h2 = ParagraphStyle('H2', fontSize=13, fontName='Helvetica-Bold', textColor=DARK_BG, spaceBefore=12, spaceAfter=6)
style_body = ParagraphStyle('Body', fontSize=10, fontName='Helvetica', textColor=HexColor('#374151'), leading=14, spaceAfter=4)
style_bold = ParagraphStyle('Bold', fontSize=10, fontName='Helvetica-Bold', textColor=HexColor('#374151'), leading=14, spaceAfter=2)
style_small = ParagraphStyle('Small', fontSize=9, fontName='Helvetica', textColor=HexColor('#6B7280'), leading=12, spaceAfter=2)
style_check = ParagraphStyle('Check', fontSize=10, fontName='Helvetica', textColor=GREEN_TEXT, leading=14, spaceAfter=2)
style_pending = ParagraphStyle('Pending', fontSize=10, fontName='Helvetica', textColor=ORANGE_TEXT, leading=14, spaceAfter=2)


def add_colored_section(story, title, color_bg, color_text):
    """Add a colored section header."""
    data = [[Paragraph(f'<font color="white">{title}</font>',
             ParagraphStyle('SH', fontSize=14, fontName='Helvetica-Bold', textColor=white))]]
    t = Table(data, colWidths=[170*mm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), color_bg),
        ('TOPPADDING', (0, 0), (-1, -1), 8),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
        ('LEFTPADDING', (0, 0), (-1, -1), 12),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
    ]))
    story.append(t)
    story.append(Spacer(1, 8))


def add_item_card(story, number, title, details, status, status_color):
    """Add an audit item card."""
    status_style = ParagraphStyle('Status', fontSize=9, fontName='Helvetica-Bold', textColor=white)
    title_style = ParagraphStyle('ItemTitle', fontSize=11, fontName='Helvetica-Bold', textColor=DARK_BG)
    detail_style = ParagraphStyle('Detail', fontSize=9, fontName='Helvetica', textColor=HexColor('#4B5563'), leading=12)

    # Build details text
    detail_parts = []
    for key, value in details.items():
        detail_parts.append(f'<b>{key}</b> : {value}')
    detail_text = '<br/>'.join(detail_parts)

    # Status badge
    status_data = [[Paragraph(f'<font color="white">{status}</font>', status_style)]]
    status_table = Table(status_data, colWidths=[80])
    status_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), status_color),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('ROUNDEDCORNERS', [4, 4, 4, 4]),
    ]))

    # Main card
    card_data = [[
        Paragraph(f'{number}. {title}', title_style),
        status_table
    ], [
        Paragraph(detail_text, detail_style),
        ''
    ]]
    card = Table(card_data, colWidths=[125*mm, 40*mm])
    card.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), white),
        ('BOX', (0, 0), (-1, -1), 0.5, GRAY_BORDER),
        ('TOPPADDING', (0, 0), (-1, 0), 8),
        ('BOTTOMPADDING', (0, -1), (-1, -1), 8),
        ('LEFTPADDING', (0, 0), (-1, -1), 10),
        ('RIGHTPADDING', (0, 0), (-1, -1), 10),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('VALIGN', (1, 0), (1, 0), 'MIDDLE'),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
        ('SPAN', (0, 1), (1, 1)),
    ]))
    story.append(card)
    story.append(Spacer(1, 6))


def add_future_item(story, number, title, description, recommendation):
    """Add a future improvement item."""
    title_style = ParagraphStyle('FTitle', fontSize=11, fontName='Helvetica-Bold', textColor=DARK_BG)
    desc_style = ParagraphStyle('FDesc', fontSize=9, fontName='Helvetica', textColor=HexColor('#4B5563'), leading=12)

    card_data = [[
        Paragraph(f'{number}. {title}', title_style),
    ], [
        Paragraph(f'{description}<br/><b>Recommandation</b> : {recommendation}', desc_style),
    ]]
    card = Table(card_data, colWidths=[165*mm])
    card.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), white),
        ('BOX', (0, 0), (-1, -1), 0.5, GRAY_BORDER),
        ('TOPPADDING', (0, 0), (-1, 0), 8),
        ('BOTTOMPADDING', (0, -1), (-1, -1), 8),
        ('LEFTPADDING', (0, 0), (-1, -1), 10),
        ('RIGHTPADDING', (0, 0), (-1, -1), 10),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
    ]))
    story.append(card)
    story.append(Spacer(1, 6))


def build_pdf(output_path):
    doc = SimpleDocTemplate(
        output_path,
        pagesize=A4,
        leftMargin=20*mm,
        rightMargin=20*mm,
        topMargin=20*mm,
        bottomMargin=20*mm
    )

    story = []

    # ==================== COVER / HEADER ====================
    # Title block
    header_data = [[
        Paragraph('AUDIT COMPLET', ParagraphStyle('MainTitle', fontSize=28, fontName='Helvetica-Bold', textColor=DARK_BG)),
    ], [
        Paragraph('Projet GMAO Symfony', ParagraphStyle('SubTitle', fontSize=18, fontName='Helvetica', textColor=PRIMARY)),
    ]]
    header = Table(header_data, colWidths=[170*mm])
    header.setStyle(TableStyle([
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
    ]))
    story.append(header)
    story.append(Spacer(1, 4))

    # Meta info
    meta_data = [[
        Paragraph('<b>Date</b> : 15 mars 2026', style_body),
        Paragraph('<b>Mentor</b> : Claude (Assistant IA)', style_body),
    ]]
    meta = Table(meta_data, colWidths=[85*mm, 85*mm])
    story.append(meta)
    story.append(Spacer(1, 4))

    # Summary bar
    summary_data = [[
        Paragraph('<font color="white"><b>7 CORRIGES</b></font>',
                  ParagraphStyle('S', fontSize=10, fontName='Helvetica-Bold', textColor=white, alignment=TA_CENTER)),
        Paragraph('<font color="white"><b>3 A FAIRE</b></font>',
                  ParagraphStyle('S', fontSize=10, fontName='Helvetica-Bold', textColor=white, alignment=TA_CENTER)),
        Paragraph('<font color="white"><b>5 FUTURS</b></font>',
                  ParagraphStyle('S', fontSize=10, fontName='Helvetica-Bold', textColor=white, alignment=TA_CENTER)),
        Paragraph('<font color="white"><b>16 VALIDES</b></font>',
                  ParagraphStyle('S', fontSize=10, fontName='Helvetica-Bold', textColor=white, alignment=TA_CENTER)),
    ]]
    summary = Table(summary_data, colWidths=[42.5*mm, 42.5*mm, 42.5*mm, 42.5*mm])
    summary.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (0, 0), GREEN_BG),
        ('BACKGROUND', (1, 0), (1, 0), ORANGE_BG),
        ('BACKGROUND', (2, 0), (2, 0), BLUE_BG),
        ('BACKGROUND', (3, 0), (3, 0), HexColor('#8B5CF6')),
        ('TOPPADDING', (0, 0), (-1, -1), 10),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 10),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
    ]))
    story.append(summary)
    story.append(Spacer(1, 12))

    story.append(HRFlowable(width="100%", thickness=1, color=GRAY_BORDER))
    story.append(Spacer(1, 8))

    # ==================== NIVEAU 1 ====================
    add_colored_section(story, 'NIVEAU 1 -- Bugs / Incoherences corriges', RED_BG, white)

    add_item_card(story, 1, 'Typo route app_Dashboard', {
        'Fichier': 'DashboardController.php',
        'Probleme': 'Majuscule incoherente avec les autres routes',
        'Solution': 'Renomme en app_dashboard_index',
    }, 'CORRIGE', GREEN_BG)

    add_item_card(story, 2, 'Typo template dashborad.html.twig', {
        'Fichier': 'templates/dashboard/',
        'Probleme': 'Faute de frappe dans le nom du fichier',
        'Solution': 'Renomme en dashboard.html.twig',
    }, 'CORRIGE', GREEN_BG)

    add_item_card(story, 3, "Email d'invitation hardcode", {
        'Fichier': 'AdminUserController.php',
        'Probleme': 'Adresse email hardcodee au lieu du parametre centralise',
        'Solution': '$this->getParameter(\'mailer_from_address\')',
    }, 'CORRIGE', GREEN_BG)

    add_item_card(story, 4, 'Flash messages manquants', {
        'Fichiers': 'SiteController, BatimentController',
        'Probleme': 'Pas de feedback utilisateur apres new/edit/delete',
        'Solution': 'addFlash() ajoute sur toutes les actions',
    }, 'CORRIGE', GREEN_BG)

    add_item_card(story, 5, 'Textes de suppression en anglais', {
        'Fichiers': 'site, batiment, equipement _delete_form.html.twig',
        'Probleme': '"Are you sure..." au lieu du francais',
        'Solution': 'Traduit en "Etes-vous sur de vouloir supprimer..."',
    }, 'CORRIGE', GREEN_BG)

    add_item_card(story, '5bis', 'Suppression avec contraintes FK', {
        'Fichiers': 'Site, Batiment, Equipement, CategorieEquipement Controllers',
        'Probleme': 'Crash SQL lors de la suppression avec dependances',
        'Solution': 'Verification des dependances + message flash explicatif',
    }, 'CORRIGE', GREEN_BG)

    # ==================== NIVEAU 2 ====================
    story.append(PageBreak())
    add_colored_section(story, 'NIVEAU 2 -- Ameliorations fonctionnelles', ORANGE_BG, white)

    add_item_card(story, 6, "Gestion des utilisateurs pour l'Admin", {
        'Probleme initial': "L'admin pouvait inviter mais pas gerer les utilisateurs",
        'Solution': 'Page liste + activation/desactivation + changement de role + suppression',
        'Routes': '/admin/user/, toggle, role, delete',
    }, 'CORRIGE', GREEN_BG)

    add_item_card(story, 7, 'Page profil utilisateur', {
        'Probleme initial': 'Aucune page pour voir/modifier son profil ou changer son mot de passe',
        'Solution': 'ProfileController avec 3 pages (voir, modifier, mot de passe)',
        'Routes': '/profile/, /profile/edit, /profile/change-password',
    }, 'CORRIGE', GREEN_BG)

    add_item_card(story, 8, 'Voter centralise pour isolation organisation', {
        'Probleme': 'Code duplique denyAccessUnlessSameOrganisation() dans 4 controllers',
        'Impact': 'Si la logique change, 4 fichiers a modifier',
        'Recommandation': 'Creer un Voter unique OrganisationVoter',
    }, 'A FAIRE', ORANGE_BG)

    add_item_card(story, 9, 'Photos orphelines sur le serveur', {
        'Probleme': 'Doctrine supprime les entites Photo mais pas les fichiers physiques',
        'Impact': 'Accumulation de fichiers orphelins dans var/uploads/photos/',
        'Recommandation': 'EventSubscriber Doctrine preRemove',
    }, 'A FAIRE', ORANGE_BG)

    add_item_card(story, 10, 'Validation fichiers photos', {
        'Probleme': 'FileUploadService accepte tout type de fichier sans restriction',
        'Impact': 'Upload possible de PDF, .exe, fichiers de 500 Mo',
        'Recommandation': 'Contraintes Assert\\File (mimeTypes: image/*, maxSize: 5M)',
    }, 'A FAIRE', ORANGE_BG)

    # ==================== NIVEAU 3 ====================
    story.append(PageBreak())
    add_colored_section(story, 'NIVEAU 3 -- Ameliorations futures recommandees', GREEN_BG, white)

    add_future_item(story, 11, 'Reporting avec graphiques',
        'Le reporting affiche des tableaux mais pas de visualisations.',
        'Ajouter Chart.js pour graphiques et camemberts')

    add_future_item(story, 12, 'Export CSV/PDF',
        "Aucune fonctionnalite d'export des donnees.",
        "Boutons d'export sur les listes (demandes, interventions, reporting)")

    add_future_item(story, 13, 'Systeme de commentaires',
        'Pas de communication entre demandeur et planificateur/technicien.',
        'Entite Commentaire liee aux Demandes et Interventions')

    add_future_item(story, 14, 'Maintenance preventive',
        'Le systeme gere uniquement les demandes correctives.',
        'Planification de maintenances recurrentes sur les equipements')

    add_future_item(story, 15, "Logs / Historique d'actions",
        "Pas de tracabilite des changements.",
        'Audit trail (qui a modifie quoi, quand)')

    # ==================== POINTS POSITIFS ====================
    story.append(Spacer(1, 12))
    add_colored_section(story, 'POINTS POSITIFS VALIDES', HexColor('#8B5CF6'), white)

    check_items = [
        ('Twig templates lint', '59/59 valides'),
        ('Container Symfony', 'Aucune erreur'),
        ('Doctrine schema vs DB', 'Synchronise'),
        ('Tests PHPUnit', '24/24 passent (74 assertions)'),
        ('Routes', 'Toutes matchent'),
        ('Isolation multi-tenant', 'Via Voters + checks'),
        ('Workflow Demande - Intervention', 'Complet avec transitions'),
        ('Notifications email', '7 templates email'),
        ('Dark mode', '100% toutes pages'),
        ('Pagination', 'Toutes les listes'),
        ('Filtres/recherche', 'Demandes, interventions, equipements'),
        ('CSRF protection', 'Tous les formulaires'),
        ('Remember-me', '7 jours'),
        ('Redirection intelligente login', 'Via HomeController selon le role'),
        ('Gestion utilisateurs Admin', 'Liste + toggle + role + suppression'),
        ('Profil utilisateur', 'Voir + modifier + mot de passe'),
    ]

    check_style = ParagraphStyle('CS', fontSize=9, fontName='Helvetica', textColor=HexColor('#374151'), leading=12)
    check_bold = ParagraphStyle('CB', fontSize=9, fontName='Helvetica-Bold', textColor=GREEN_TEXT, leading=12)

    table_data = [[
        Paragraph('<b>Aspect</b>', ParagraphStyle('TH', fontSize=9, fontName='Helvetica-Bold', textColor=HexColor('#374151'))),
        Paragraph('<b>Verdict</b>', ParagraphStyle('TH', fontSize=9, fontName='Helvetica-Bold', textColor=HexColor('#374151'))),
    ]]
    for aspect, verdict in check_items:
        table_data.append([
            Paragraph(aspect, check_style),
            Paragraph(f'OK  {verdict}', check_bold),
        ])

    check_table = Table(table_data, colWidths=[85*mm, 85*mm])
    check_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), GRAY_BG),
        ('BACKGROUND', (0, 1), (-1, -1), white),
        ('BOX', (0, 0), (-1, -1), 0.5, GRAY_BORDER),
        ('LINEBELOW', (0, 0), (-1, 0), 1, GRAY_BORDER),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [white, HexColor('#F9FAFB')]),
        ('TOPPADDING', (0, 0), (-1, -1), 5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('ROUNDEDCORNERS', [6, 6, 6, 6]),
        ('GRID', (0, 0), (-1, -1), 0.25, GRAY_BORDER),
    ]))
    story.append(check_table)

    # ==================== PROCHAINES ETAPES ====================
    story.append(PageBreak())
    add_colored_section(story, 'PROCHAINES ETAPES RECOMMANDEES', DARK_BG, white)

    story.append(Paragraph('<b>Phase immediate (corrections restantes) :</b>', style_h2))
    immediate_items = [
        'Creer un Voter centralise pour isolation organisation (point 8)',
        'Ajouter la validation des fichiers photos -- type MIME + taille max (point 10)',
        'Gerer la suppression des fichiers physiques photos orphelins (point 9)',
    ]
    for i, item in enumerate(immediate_items, 1):
        story.append(Paragraph(f'  {i}. {item}', style_body))

    story.append(Spacer(1, 12))
    story.append(Paragraph('<b>Phase ameliorations :</b>', style_h2))
    improvement_items = [
        'Reporting avec graphiques Chart.js (point 11)',
        'Export CSV/PDF (point 12)',
        'Systeme de commentaires (point 13)',
        'Maintenance preventive (point 14)',
        "Logs / Historique d'actions (point 15)",
    ]
    for i, item in enumerate(improvement_items, 4):
        story.append(Paragraph(f'  {i}. {item}', style_body))

    # ==================== FOOTER NOTE ====================
    story.append(Spacer(1, 30))
    story.append(HRFlowable(width="100%", thickness=1, color=GRAY_BORDER))
    story.append(Spacer(1, 8))
    footer_style = ParagraphStyle('Footer', fontSize=8, fontName='Helvetica-Oblique', textColor=HexColor('#9CA3AF'), alignment=TA_CENTER)
    story.append(Paragraph('Audit genere automatiquement -- Projet GMAO Symfony 8 -- Mars 2026', footer_style))

    doc.build(story)
    print(f"PDF genere avec succes : {output_path}")


if __name__ == '__main__':
    build_pdf('/Users/diakite/GMAO/GMAO/audit-gmao.pdf')
