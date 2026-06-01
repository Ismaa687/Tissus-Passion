<?php
/* ============================================================
   TISSUS PASSION — Données du catalogue produits
   Tableau de 9 tissus vendus au mètre en XPF
   Les images sont à placer dans le dossier img/ à la racine.
   Format recommandé : JPG ou WebP, ratio 4:3, min 600×450 px.
   ============================================================ */

$PRODUCTS = [
    [
        'id'          => 1,
        'nom'         => 'Coton Imprimé Floral',
        'description' => 'Motifs floraux tropicaux aux couleurs vives. Idéal pour coussins, rideaux et nappes. Doux au toucher, facile d\'entretien.',
        'prix'        => 1200,
        'categorie'   => 'Coton',
        'couleur'     => 'linear-gradient(135deg, #E8A87C, #C0784A)', // fallback si image absente
        'image'       => 'img/coton_floral.jpg',
    ],
    [
        'id'          => 2,
        'nom'         => 'Velours Terracotta',
        'description' => 'Velours ras aux reflets chauds. Parfait pour tapisserie de canapé ou têtes de lit. Toucher soyeux et grande durabilité.',
        'prix'        => 3800,
        'categorie'   => 'Velours',
        'couleur'     => 'linear-gradient(135deg, #D4956A, #8B5030)',
        'image'       => 'img/velours-terracotta.jpg',
    ],
    [
        'id'          => 3,
        'nom'         => 'Lin Naturel Écru',
        'description' => 'Lin brut d\'aspect noble, respirant et naturel. Idéal pour rideaux, voilages et coussins style bohème ou scandinave.',
        'prix'        => 1800,
        'categorie'   => 'Lin',
        'couleur'     => 'linear-gradient(135deg, #D9CBAD, #A89268)',
        'image'       => 'img/lin-naturel-ecru.jpg',
    ],
    [
        'id'          => 4,
        'nom'         => 'Tissu Paréo Tahitien',
        'description' => 'Tissu léger inspiré des tifaifai polynésiens. Motifs géométriques et floraux traditionnels. Parfait pour décoration estivale.',
        'prix'        => 950,
        'categorie'   => 'Traditionnel',
        'couleur'     => 'linear-gradient(135deg, #6EC6CA, #2A8A8E)',
        'image'       => 'img/tissu-pareo-tahitien.jpg',
    ],
    [
        'id'          => 5,
        'nom'         => 'Jacquard Prestige Doré',
        'description' => 'Tissage jacquard à motifs damassés, reflets dorés. Haut de gamme pour rideaux de salon, galons et voilures d\'apparat.',
        'prix'        => 4500,
        'categorie'   => 'Jacquard',
        'couleur'     => 'linear-gradient(135deg, #C9B89A, #8A7048)',
        'image'       => 'img/jacquard-prestige-dore.jpg',
    ],
    [
        'id'          => 6,
        'nom'         => 'Microfibre Anthracite',
        'description' => 'Microfibre ultra-douce, imperméable et facile à nettoyer. Parfaite pour revêtement de chaises, banquettes et mobilier enfant.',
        'prix'        => 2200,
        'categorie'   => 'Synthétique',
        'couleur'     => 'linear-gradient(135deg, #6B7280, #374151)',
        'image'       => 'img/microfibre-anthracite.jpg',
    ],
    [
        'id'          => 7,
        'nom'         => 'Soie Sauvage Ivoire',
        'description' => 'Soie sauvage naturelle, légèrement texturée. Luminosité incomparable pour rideaux de prestige et tentures murales.',
        'prix'        => 4200,
        'categorie'   => 'Soie',
        'couleur'     => 'linear-gradient(135deg, #F5ECD7, #D4C5A0)',
        'image'       => 'img/soie-sauvage-ivoire.jpg',
    ],
    [
        'id'          => 8,
        'nom'         => 'Coton Popeline Vert Sauge',
        'description' => 'Popeline de coton fin, ton vert sauge apaisant. Parfait pour nappes, coussins de jardin et déco intérieure naturelle.',
        'prix'        => 1400,
        'categorie'   => 'Coton',
        'couleur'     => 'linear-gradient(135deg, #8FAE88, #4A6B42)',
        'image'       => 'img/coton-popeline-vert-sauge.jpg',
    ],
    [
        'id'          => 9,
        'nom'         => 'Broderie Anglaise Blanche',
        'description' => 'Tissu coton brodé de motifs ajourés délicats. Idéal pour voilages, napperons, robes d\'intérieur et décoration romantique.',
        'prix'        => 2600,
        'categorie'   => 'Coton',
        'couleur'     => 'linear-gradient(135deg, #FFFFFF, #E8DDD0)',
        'image'       => 'img/broderie-anglaise-blanche.jpg',
    ],
];
