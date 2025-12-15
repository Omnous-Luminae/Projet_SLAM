<?php
/**
 * Système de Breadcrumbs (Fil d'Ariane)
 * Usage: include breadcrumbs.php puis appeler renderBreadcrumbs($items)
 */

function renderBreadcrumbs($items) {
    if (empty($items)) return;
    ?>
    <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <?php foreach ($items as $index => $item): ?>
            <?php if ($index > 0): ?>
                <span class="breadcrumb-separator">›</span>
            <?php endif; ?>
            
            <?php if (isset($item['url']) && $index < count($items) - 1): ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" class="breadcrumb-link">
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php else: ?>
                <span class="breadcrumb-current">
                    <?= htmlspecialchars($item['label']) ?>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
}

function getBreadcrumbStyles() {
    return <<<CSS
    <style>
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            background: var(--card-bg, #fff);
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            font-size: 0.95em;
            flex-wrap: wrap;
        }

        .breadcrumb-link {
            color: var(--primary-color, #667eea);
            text-decoration: none;
            transition: all 0.3s;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .breadcrumb-link:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateX(-2px);
        }

        .breadcrumb-separator {
            color: var(--text-color, #666);
            opacity: 0.4;
            font-weight: bold;
        }

        .breadcrumb-current {
            color: var(--text-color, #333);
            font-weight: 600;
            padding: 5px 10px;
        }

        [data-theme="dark"] .breadcrumbs {
            background: var(--card-bg, #1e293b);
        }

        @media (max-width: 768px) {
            .breadcrumbs {
                font-size: 0.85em;
                padding: 10px 15px;
            }
        }
    </style>
CSS;
}
?>
