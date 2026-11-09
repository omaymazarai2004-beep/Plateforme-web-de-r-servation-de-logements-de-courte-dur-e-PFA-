<?php
// admin/mcd.php
$page_title = "MCD — Modèle Conceptuel de Données";
require_once 'includes/header.php';
?>

<div class="page active">
  <div style="margin-bottom:24px;">
    <h2 style="font-family:'Playfair Display';font-size:24px;">MCD — Modèle Conceptuel de Données</h2>
    <p style="color:var(--text-light);">Corrigé selon les remarques de Mme Zoulele Kouki (email du 6 avril 2026)</p>
  </div>

  <div class="alert success" style="background:#ecfdf5;color:#065f46;padding:14px 20px;border-radius:12px;margin-bottom:20px;">
    <i class="fa-solid fa-circle-check"></i> MCD mis à jour : clés étrangères retirées des entités, cardinalité Date = 2 (date_deb + date_fin), clé primaire Réservation = combinaison unique (id_client, id_logement, date_deb, date_fin).
  </div>

  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-diagram-project"></i> Entités et leurs attributs</h3>
    </div>
    
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-top:16px;">
      
      <!-- Client -->
      <div class="mcd-entity" style="border:2px solid var(--rose);border-radius:12px;overflow:hidden;">
        <h4 style="background:var(--rose);color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-user"></i> Client</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="font-weight:700;color:var(--rose);">🔑 id_client (PK)</li>
            <li>nom</li><li>prénom</li>
            <li>email (unique)</li><li>téléphone</li>
            <li>adresse</li><li>ville</li><li>pays</li>
            <li>type_client</li><li>mot_de_passe</li>
            <li>date_inscription</li><li>statut</li>
          </ul>
        </div>
      </div>

      <!-- Logement -->
      <div class="mcd-entity" style="border:2px solid var(--rose);border-radius:12px;overflow:hidden;">
        <h4 style="background:var(--rose);color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-building"></i> Logement</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="font-weight:700;color:var(--rose);">🔑 id_logement (PK)</li>
            <li>titre</li><li>description</li>
            <li>type</li><li>région_ville</li>
            <li>prix_nuit</li><li>nb_lits</li>
            <li>nb_chambres</li><li>nb_sdb</li>
            <li>surface</li><li>statut</li>
            <li>adresse_exacte</li>
          </ul>
        </div>
      </div>

      <!-- Réservation (clé composite) -->
      <div class="mcd-entity" style="border:2px solid var(--info);border-radius:12px;overflow:hidden;">
        <h4 style="background:var(--info);color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-calendar-check"></i> Réservation</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="color:var(--info);font-style:italic;">🔗 id_client (FK) ←</li>
            <li style="color:var(--info);font-style:italic;">🔗 id_logement (FK) ←</li>
            <li style="color:var(--info);font-style:italic;">🔗 date_deb (FK) ← Date</li>
            <li style="color:var(--info);font-style:italic;">🔗 date_fin (FK) ← Date</li>
            <li style="font-size:11px;color:#6b7280;">PK = (id_client, id_logement, date_deb, date_fin)</li>
            <li>montant_total</li><li>statut</li>
            <li>date_creation</li><li>nb_voyageurs</li>
          </ul>
        </div>
      </div>

      <!-- Date -->
      <div class="mcd-entity" style="border:2px solid var(--success);border-radius:12px;overflow:hidden;">
        <h4 style="background:var(--success);color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-calendar"></i> Date</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="font-style:italic;color:#6b7280;">Pas d'identifiant propre</li>
            <li>date_deb</li><li>date_fin</li>
            <li>nb_nuits</li><li>disponible</li>
          </ul>
        </div>
      </div>

      <!-- Demande -->
      <div class="mcd-entity" style="border:2px solid var(--rose);border-radius:12px;overflow:hidden;">
        <h4 style="background:var(--rose);color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-inbox"></i> Demande</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="font-weight:700;color:var(--rose);">🔑 id_demande (PK)</li>
            <li>date_deb</li><li>date_fin</li>
            <li>nb_nuits</li><li>budget</li>
            <li>région_souhaitée</li><li>statut</li>
            <li>description</li><li>nb_voyageurs</li>
          </ul>
        </div>
      </div>

      <!-- Hôte -->
      <div class="mcd-entity" style="border:2px solid var(--warning);border-radius:12px;overflow:hidden;">
        <h4 style="background:var(--warning);color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-user-tie"></i> Hôte</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="font-weight:700;color:var(--rose);">🔑 id_hôte (PK)</li>
            <li style="color:var(--info);font-style:italic;">🔗 id_client (FK) ←</li>
            <li>statut_hôte</li><li>date_debut</li>
            <li>commission</li>
          </ul>
        </div>
      </div>

      <!-- Photo -->
      <div class="mcd-entity" style="border:2px solid #8b5cf6;border-radius:12px;overflow:hidden;">
        <h4 style="background:#8b5cf6;color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-images"></i> Photo</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="font-weight:700;color:var(--rose);">🔑 id_photo (PK)</li>
            <li style="color:var(--info);font-style:italic;">🔗 id_logement (FK) ←</li>
            <li style="color:var(--info);font-style:italic;">🔗 id_hôte (FK) ←</li>
            <li>url</li><li>description</li>
            <li>statut</li><li>date_upload</li>
            <li>est_principale</li>
          </ul>
        </div>
      </div>

      <!-- Notification -->
      <div class="mcd-entity" style="border:2px solid #10b981;border-radius:12px;overflow:hidden;">
        <h4 style="background:#10b981;color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-bell"></i> Notification</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="font-weight:700;color:var(--rose);">🔑 id_notification (PK)</li>
            <li style="color:var(--info);font-style:italic;">🔗 id_client (FK) ←</li>
            <li>titre</li><li>message</li>
            <li>type</li><li>lu</li>
          </ul>
        </div>
      </div>

      <!-- Correspondance -->
      <div class="mcd-entity" style="border:2px solid #f59e0b;border-radius:12px;overflow:hidden;">
        <h4 style="background:#f59e0b;color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-message"></i> Correspondance</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="font-weight:700;color:var(--rose);">🔑 id_correspondance (PK)</li>
            <li style="color:var(--info);font-style:italic;">🔗 id_expéditeur (FK) ←</li>
            <li style="color:var(--info);font-style:italic;">🔗 id_destinataire (FK) ←</li>
            <li>message</li><li>sujet</li><li>lu</li>
          </ul>
        </div>
      </div>

      <!-- Favori -->
      <div class="mcd-entity" style="border:2px solid var(--rose);border-radius:12px;overflow:hidden;">
        <h4 style="background:var(--rose);color:white;padding:10px 16px;margin:0;"><i class="fa-solid fa-heart"></i> Favori</h4>
        <div style="padding:16px;">
          <ul style="list-style:none;font-size:13px;">
            <li style="color:var(--info);font-style:italic;">🔗 id_client (FK) ←</li>
            <li style="color:var(--info);font-style:italic;">🔗 id_logement (FK) ←</li>
            <li style="font-size:11px;">PK = (id_client, id_logement)</li>
            <li>date_ajout</li>
          </ul>
        </div>
      </div>

    </div>

    <!-- Associations -->
    <div style="margin-top:32px;padding:20px;background:#f9fafb;border-radius:12px;border:1px solid var(--border);">
      <h4 style="margin-bottom:16px;font-size:16px;">📌 Associations (conformes aux corrections)</h4>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;font-size:13px;">
        <div><strong>Dépose</strong> — Client (1,1) → Demande (0,m)</div>
        <div><strong>Effectue</strong> — Client (0,m) ↔ Réservation (0,m)</div>
        <div><strong>Contacte</strong> — Client (0,m) ↔ Client (0,m) via Correspondances</div>
        <div><strong>Publie</strong> — Hôte (1,1) → Logement (0,m)</div>
        <div><strong>Alloué par</strong> — Date (0,m) ↔ Logement (0,m) [cardinalité = 2]</div>
        <div><strong>Gère</strong> — Hôte (1,1) ↔ Hôte hérite de Client (1,1)</div>
        <div><strong>Porte sur</strong> — Réservation (1,1) → Date (1,1)</div>
        <div><strong>Photos</strong> — Hôte (0,m) → Photo (0,m) → Logement</div>
        <div><strong>Reçoit</strong> — Client (1,1) → Notification (0,m)</div>
        <div><strong>Ajoute</strong> — Client (0,m) ↔ Favori (0,m) ↔ Logement</div>
      </div>
    </div>

    <!-- Diagramme visuel -->
    <div style="margin-top:32px;padding:20px;background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
      <h4 style="margin-bottom:16px;font-size:16px;">📊 Diagramme des relations</h4>
      <pre style="font-family:'Courier New',monospace;font-size:11px;overflow-x:auto;">
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Client    │────→│   Demande   │     │    Date     │
│  (PK: id)   │     │  (PK: id)   │     │ (sans PK)   │
└─────────────┘     └─────────────┘     └─────────────┘
       │                                       ↑
       │                                       │
       ↓                                       │
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Hôte      │────→│  Logement   │←────│ Réservation │
│  (PK: id)   │     │  (PK: id)   │     │ (PK: compo) │
│ (FK: client)│     └─────────────┘     └─────────────┘
└─────────────┘            │                    ↑
       │                   │                    │
       ↓                   ↓                    │
┌─────────────┐     ┌─────────────┐            │
│   Photo     │←────│   Favori    │────────────┘
│  (PK: id)   │     │ (PK: compo) │
│ (FK: logt)  │     └─────────────┘
└─────────────┘
      </pre>
    </div>
  </div>
</div>

<style>
.mcd-entity {
  transition: transform 0.2s, box-shadow 0.2s;
}
.mcd-entity:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>

<?php require_once 'includes/footer.php'; ?>