<?
include "config.php";
$title = 'Бланки';
include "header.php";
?>

<style>
	.files_wrap {
		display: flex;
		justify-content: normal;
		flex-wrap: wrap;
		margin-top: 30px;
		text-align: center;
	}
	.cell a {
		display: block;
		text-align: center;
		width: 200px;
		margin: auto;
		border: 1px solid #FFF;
	}
	.cell a:hover {
		border: 1px solid #CCC;
		box-shadow: 0 3px 9px #999;
	}
</style>

<div class="files_wrap">
	<section class="cell">
		<a href="/files/%D0%B1%D0%B8%D1%80%D0%BA%D0%B0%20%D0%BD%D0%B0%20%D0%B1%D1%80%D0%B0%D0%BA%201.pdf" class='print'>
			<p><i class="fas fa-file-pdf fa-5x"></i></p>
			<p>Бирка на брак 1</p>
		</a>
	</section>
	<section class="cell">
		<a href="/files/%D0%BA%D0%BE%D0%BB%D0%B8%D1%87%D0%B5%D1%81%D1%82%D0%B2%D0%BE%20%D0%BD%D0%B0%20%D1%80%D0%B0%D1%81%D1%84%D0%BE%D1%80%D0%BC%D0%BE%D0%B2%D0%BA%D0%B5.pdf" class='print'>
			<p><i class="fas fa-file-pdf fa-5x"></i></p>
			<p>Количество на расформовке</p>
		</a>
	</section>
	<section class="cell">
		<a href="/files/%D1%81%D0%BE%D0%BF%D1%80%D0%BE%D0%B2%D0%BE%D0%B4%D0%B8%D1%82%D0%B5%D0%BB%D1%8C%D0%BD%D1%8B%D0%B9%20%D0%BB%D0%B8%D1%81%D1%82.pdf" class='print'>
			<p><i class="fas fa-file-pdf fa-5x"></i></p>
			<p>Сопроводительный лист</p>
		</a>
	</section>
	<section class="cell">
		<a href="/files/%D1%82%D0%B0%D0%B1%D0%B5%D0%BB%D1%8C.pdf" class='print'>
			<p><i class="fas fa-file-pdf fa-5x"></i></p>
			<p>Табель</p>
		</a>
	</section>
	<section class="cell">
		<a href="/files/%D1%87%D0%B5%D0%BA-%D0%BB%D0%B8%D1%81%D1%82%20%D1%80%D0%B0%D1%81%D1%84%D0%BE%D1%80%D0%BC%D0%BE%D0%B2%D0%BA%D0%B8.pdf" class='print'>
			<p><i class="fas fa-file-pdf fa-5x"></i></p>
			<p>Чек-лист расформовки</p>
		</a>
	</section>
	<section class="cell">
		<a href="/files/%D1%87%D0%B5%D0%BA-%D0%BB%D0%B8%D1%81%D1%82%20%D1%83%D0%BF%D0%B0%D0%BA%D0%BE%D0%B2%D0%BA%D0%B8.pdf" class='print'>
			<p><i class="fas fa-file-pdf fa-5x"></i></p>
			<p>Чек-лист упаковки</p>
		</a>
	</section>
	<section class="cell">
		<a href="/files/%D0%B2%D1%8B%D0%B4%D0%B0%D1%87%D0%B0%20%D0%BF%D0%B5%D1%80%D1%87%D0%B0%D1%82%D0%BE%D0%BA.pdf" class='print'>
			<p><i class="fas fa-file-pdf fa-5x"></i></p>
			<p>Выдача перчаток</p>
		</a>
	</section>
	<section class="cell">
		<a href="/files/%D0%A0%D0%B0%D1%81%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0%20%D0%BF%D0%B5%D1%80%D1%81%D0%BE%D0%BD%D0%B0%D0%BB%D0%B0%20%D0%BD%D0%B0%20%D0%BA%D0%BE%D0%BD%D0%B2%D0%B5%D0%B9%D0%B5%D1%80%D0%B5.pdf" class='print'>
			<p><i class="fas fa-file-pdf fa-5x"></i></p>
			<p>Расстановка персонала на конвейере</p>
		</a>
	</section>
</div>

<script>
	$(function() {
		$(".print").printPage();
	});
</script>

<?
include "footer.php";
?>
