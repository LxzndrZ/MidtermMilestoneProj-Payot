<?php
// One recipe card. Expects $r (a recipe row from RecipeRepository).
?>
<article class="card recipe-card" data-recipe-card>
  <div class="card-top">
    <span class="tag"><?= e($r['category']) ?></span>
    <button type="button" class="fav-btn<?= $r['is_fav'] ? ' is-on' : '' ?>"
            data-recipe-id="<?= (int)$r['id'] ?>"
            aria-pressed="<?= $r['is_fav'] ? 'true' : 'false' ?>"
            title="Save to favorites">
      <span class="fav-icon" aria-hidden="true"><?= $r['is_fav'] ? '&#9829;' : '&#9825;' ?></span>
      <span class="fav-label"><?= $r['is_fav'] ? 'Saved' : 'Save' ?></span>
    </button>
  </div>
  <h3><a href="recipe.php?id=<?= (int)$r['id'] ?>"><?= e($r['title']) ?></a></h3>
  <p class="muted">by <?= e($r['author']) ?> &middot; <?= e(show_date($r['created_at'])) ?>
    <?php if ($r['edited_at']): ?>(edited <?= e(show_date($r['edited_at'])) ?>)<?php endif; ?></p>
  <p><?= e($r['description']) ?></p>
  <p class="muted"><span class="save-count" data-recipe-id="<?= (int)$r['id'] ?>"><?= (int)$r['saves'] ?></span> saved</p>
</article>