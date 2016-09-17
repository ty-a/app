<?php $counter = 0; ?>

<?php if ( !empty( $stories ) ) : ?>
	<div class="side-articles RailModule">
		<h1 class="side-articles-header">Related Fandom Stories</h1>
		<?php foreach ( $stories as $story ) : ?>
			<div class="side-article result">
				<div class="side-article-category"><?= $story['vertical'] ?></div>
				<div class="side-article-thumbnail">
					<? if ( isset( $story['image'] ) ) : ?>
						<a href="<?=$story['url']?>" data-pos="<?= $counter ?>">
							<img src="<?= $story['image'] ?>" />
						</a>
					<? endif; ?>
				</div>
				<div class="side-article-text">
					<a href="<?= $story['url'] ?>" data-pos="<?= $counter ?>"><?= $story['title'] ?></a>
				</div>
			</div>
			<?php if ( $counter++ >= 4 ) { break; } ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
