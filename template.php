<?php

require_once __DIR__ . '/api.php';

function esc_attr( $value ) { return ( new Element( (string) $value ) )->attr(); }
function esc_html( $value ) { return ( new Element( (string) $value ) )->content(); }
function inline_file( $file ) { return preg_replace( '#[\r\n\t\s]+?#i', ' ', file_get_contents( $file ) ); }

$akordi = new Akordi();
$data = $akordi->template_data();

?>
<!DOCTYPE html>
<html lang="lv">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style type="text/css"><?= inline_file( __DIR__ . '/style.css' ); ?></style>
	<title>Dziesmu akordi</title>
</head>
<body>
	<h1>Latviešu dziesmu akordi</h1>

	<?php foreach ( $data['authors'] as $author ) : ?>
	<details id="autors--<?= esc_attr( $author['slug'] ); ?>">
		<summary><?= esc_html( $author['name'] ); ?></summary>
		<ul>
			<?php foreach ( $author['songs'] as $song ) : ?>
			<details id="dziesma--<?= esc_attr( $song['slug'] ); ?>">
				<summary><?= esc_html( $song['title'] ); ?></summary>
				<cite><?= esc_html( $author['name'] ); ?></cite>
				<pre><?= esc_html( $song['chords'] ); ?></pre>
			</details>
			<?php endforeach; // author songs ?>
		</ul>
	</details>
	<?php endforeach; // authors ?>

	<footer>
		<p>Izejas kods: <a href="https://github.com/kasparsd/akordi">github.com/kasparsd/akordi</a></p>
	</footer>
</body>
</html>