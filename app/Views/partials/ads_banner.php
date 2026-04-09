<?php
/**
 * Ads banner partial.
 * Usage: include with $adsTarget ('club_ui' or 'member_portal')
 * Variables available from parent scope: $clubBranding (for club_id), etc.
 */
use App\Models\AdModel;
use App\Models\SubscriptionModel;
use App\Helpers\ClubContext;

$clubId    = ClubContext::current();
$plan      = 'trial';
if ($clubId) {
    try {
        $sub  = (new SubscriptionModel())->getForClub($clubId);
        $plan = $sub['plan'] ?? 'trial';
    } catch (\Throwable) {}
}

$adTarget = $adsTarget ?? 'club_ui';
$ads      = [];
try {
    $ads = (new AdModel())->getActive($adTarget, $clubId ?? 0, $plan);
} catch (\Throwable) {}

if (empty($ads)) return;
?>
<div class="ads-banner px-3 py-1">
<?php foreach ($ads as $ad): ?>
    <?php if ($ad['link_url']): ?>
    <a href="<?= e($ad['link_url']) ?>" target="_blank" rel="noopener" class="d-block text-decoration-none mb-1">
    <?php endif; ?>

    <div class="alert alert-secondary py-2 mb-1 d-flex align-items-center gap-2" style="font-size:.9rem">
        <?php if ($ad['image_path']): ?>
            <img src="<?= e($ad['image_path']) ?>" alt="" style="max-height:40px;max-width:80px;object-fit:contain">
        <?php else: ?>
            <i class="bi bi-megaphone text-primary flex-shrink-0"></i>
        <?php endif; ?>
        <div><?= $ad['content'] /* allowed HTML */ ?></div>
    </div>

    <?php if ($ad['link_url']): ?>
    </a>
    <?php endif; ?>
<?php endforeach; ?>
</div>
