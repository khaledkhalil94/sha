	<div class="ui container">
		<div class="ui image tiny msg-image">
			<img src="<?= $img_path ?>" style="width:165px;">
		</div>
		<div class="msg-main">
			<div class="msg-user_info">

					<h4>Sent to <a href="/sha/user/<?= $message->user_id ?>/"><?= $message->r_name; ?></a></h4>

				<div class="time" title="<?= $date; ?>"><?= $timeAgo; ?></div>

			</div>
			<hr>
			<div class="msg-body">
				<div class="content"><?= $message->subject; ?></div>
			</div>
		</div>
	</div>
	<hr>
	<div">
		<a class="ui primary button" href="./?pm=<?= $message->user_id ?>">Back</a>
	</div>