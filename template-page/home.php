<?php

/**
 * Template Name: Home
 */

get_header();

$services = AltegioClient::getServices();
$staff = AltegioClient::getStaff();
?>

<main class="container" style="padding: 40px 0;">
    <h1>Available Services</h1>

    <?php if (!empty($services['data'])): ?>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 60px;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #ccc;">
                    <th style="padding: 8px;">#</th>
                    <th style="padding: 8px;">Title</th>
                    <th style="padding: 8px;">Min Price</th>
                    <th style="padding: 8px;">Max Price</th>
                    <th style="padding: 8px;">Duration (min)</th>
                    <th style="padding: 8px;">Comment</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services['data'] as $i => $service): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px;"><?php echo $i + 1; ?></td>
                        <td style="padding: 8px;"><?php echo esc_html($service['title'] ?? '—'); ?></td>
                        <td style="padding: 8px;"><?php echo esc_html($service['price_min'] ?? '-'); ?> ₴</td>
                        <td style="padding: 8px;"><?php echo esc_html($service['price_max'] ?? '-'); ?> ₴</td>
                        <td style="padding: 8px;"><?php echo isset($service['duration']) ? round($service['duration'] / 60) : '-'; ?></td>
                        <td style="padding: 8px;"><?php echo esc_html($service['comment'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: red;">Failed to fetch services: <?php echo esc_html($services['error'] ?? 'Unknown issue'); ?></p>
    <?php endif; ?>

    <h1>Our Team</h1>
    <pre style="background: #f6f6f6; padding: 20px; border-radius: 10px; overflow-x: auto;">
<?php
function render_pretty_array($array, $depth = 0)
{
    $indent = str_repeat('    ', $depth);
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            echo "{$indent}<strong>$key:</strong><br>";
            render_pretty_array($value, $depth + 1);
        } else {
            $safeVal = is_bool($value) ? ($value ? 'true' : 'false') : htmlspecialchars((string)$value);
            echo "{$indent}<strong>$key:</strong> $safeVal<br>";
        }
    }
}

$firstStaff = $staff['data'][0] ?? [];
render_pretty_array($firstStaff);
?>
</pre>





</main>

<?php get_footer(); ?>