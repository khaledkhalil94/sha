<br>
<br>
<br>
<?php $comments = Comment::get_comments($id); ?>

<h3>Comments (<span id="commentscount"><?= count($comments); ?></span>): </h3>
<form class="ui reply form" action="">
	<div class="field">
		<textarea name="content" id="comment-submit-textarea" rows="2" placeholder="Add a new comment.."></textarea>
	</div>
	<input type="hidden" name="post_id" class="form-control" value="<?= $id; ?>" >
	<input type="hidden" name="uid" class="form-control" value="<?= USER_ID; ?>" >
	<button name="comment" id="subcomment" style="display:none;" class="ui blue submit disabled icon button">Submit</button>
</form>
<hr>
<div id="comments">
<?php if(count($comments) === 0) {
		echo "<span id=\"emptycmt\">There is nothing here yet, be the first to comment!</span>";
	} else {
		foreach ($comments as $comment):
			$voted = QNA::has_voted($comment->id, USER_ID);
			$votes = Comment::get_votes($comment->id); 
			$commenter = Student::find_by_id($comment->uid);
			$self = $comment->uid === USER_ID;
			$reports_count = QNA::get_reports("comments", $comment->id) ? QNA::get_reports("comments", $comment->id)[0]->count : null; 
			$reports_count = $reports_count > 1 ? "This comment has been reported ".$reports_count." times." : ($reports_count === NULL ? NULL : "This comment has been reported once.");

			$img_path = ProfilePicture::get_profile_pic(Student::find_by_id($comment->uid));

			$comment_date = $comment->created;
			$comment_edited_date = $comment->last_modified;

			if($comment->last_modified > $comment->created){
				$edited = "(edited <span id='editedDate' title=\"$comment_edited_date\">$comment_edited_date</span>)";
			} else {
				$edited = "";
			}
			?>
				<?php if($session->adminCheck()) {?>
				<a style="color:red;" href="/sha/staff/admin/questions/reports.php#id=<?= $id; ?>">
				 <?= $reports_count; ?></a>
				<?php } ?>

				<div class="ui minimal comments">
					<div class="ui comment padded segment" id="<?= $comment->id; ?>">
						<a class="avatar" href="/sha/students/<?= $comment->uid; ?>/">
							<img src="<?= $img_path; ?>">
						</a>
						<div class="content">
							<a class="author" href="<?= BASE_URL."students/".$commenter->id; ?>/"><?= $commenter->full_name();?></a>
							<div class="metadata">
								<a class="time" href="question.php?id=<?= $comment->id; ?>"><span id="commentDate" title="<?=$comment_date;?>"><?= $comment_date;?></span></a><?= $edited; ?>
							</div>
							<div class="text">
								<h4><?= $comment->content; ?></h4>
							</div>
							<?php if($voted){ ?>
									<div class="comment-points">
										<a class="comment-vote-btn voted"><i class="heart circular red icon"></i></a>
										<span class="comment-votes-count"><?=$votes;?></span>
									</div>
							<?php } else { ?>
									<div class="comment-points">
										<a class="comment-vote-btn"><i class="heart circular icon"></i></a><span class="comment-votes-count"><?=$votes;?> </span>
									</div>
							<?php 
							} ?>
							<div title="Actions" class="ui pointing dropdown" id="comment-actions">
								<i class="ellipsis link big horizontal icon"></i>
								<div class="menu">
									<?php if ($self || $session->adminCheck()) { ?>
										<div class="item" id="edit">
											<a class="edit">Edit</a>
										</div>
										<div class="item" id="del">
											<a class="delete">Delete</a>
										</div>
									<?php } ?>
									<?php if (!$self) { ?>
										<div class="item" id="post_report">
											<a class="report">Report</a>
										</div>
									<?php } ?>
								</div>
							</div>

						</div>
					</div>
				</div>

		<?php endforeach; 
	}?>
</div>


<div class="ui small modal comment delete">
	<div class="ui segment">
		<div class="header">
			<h3>DELETE</h3>
		</div>
		<div class="content">
			<div class="description">
				<h4>Are you sure you want to delete this comment ? This action cannot be undone.</h4><br>
				<div class="ui teal message" style="text-align:left;">
					<p></p>
				</div>
			</div>
		</div>
		<div class="actions">
			<div class="ui white deny button">
				Cancel
			</div>
			<div class="ui blue button" id="comment-confirmDel">
				Delete
			</div>
		</div>
	</div>
</div>

<div class="ui small modal post delete">
	<div class="ui segment">
		<div class="header">
			<h3>DELETE</h3>
		</div>
		<div class="content">
			<div class="description">
				<p>This question and all it's comments will be deleted permanently , are you sure you want to continue ?</p><br>
			</div>
		</div>
		<div class="actions">
			<div class="ui white deny button">
				Cancel
			</div>
			<div class="ui blue button" id="post-confirmDel">
				Delete
			</div>
		</div>
	</div>
</div>

<div class="ui small modal post unpublish">
	<div class="ui segment">
		<div class="header">
			<h3>unPublish Post</h3>
		</div>
		<div class="content">
			<div class="description">
				<p>Are you sure you want to list this post as unpublished ? Only you will be able to view it.</p><br>
			</div>
		</div>
		<div class="actions">
			<div class="ui white deny button">
				Cancel
			</div>
			<div class="ui blue button" id="post-confirmUnP">
				Confirm
			</div>
		</div>
	</div>
</div>

<div class="ui small modal post publish">
	<div class="ui segment">
		<div class="header">
			<h3>Publish Post</h3>
		</div>
		<div class="content">
			<div class="description">
				<p>By publishing this post, anyone can see and interact with it.</p><br>
			</div>
		</div>
		<div class="actions">
			<div class="ui white deny button">
				Cancel
			</div>
			<div class="ui blue button" id="post-confirmPub">
				Confirm
			</div>
		</div>
	</div>
</div>


<div class="ui small modal report">
	<div class="ui segment">
		<div class="header">
			<h3>REPORT</h3>
		</div>
		<div class="content">
			<div class="description">
				<h4>Are you sure you want to report this post ?</h4>
				<div class="ui teal message" style="text-align:left;">
					<p></p>
				</div>
				<br>
			</div>
			<div title="THIS SHIT IS BUGGED, I WILL FIX IT LATER" class="ui toggle checkbox">
				<input type="checkbox" name="public" class="hidden" tabindex="0">
				<label>Add message</label>
			</div>
			<div class="ui form" id="modalForm" style="display:none;">
				<div class="field">
					<textarea rows="4"></textarea>
				</div>
			</div>
		</div>
		<div class="actions">
			<div class="ui white deny button" id="report-cancel">
				Cancel
			</div>
			<div class="ui blue button" id="report-confirm">
				Report
			</div>
		</div>
	</div>
</div>




<script>

// $('.ui.dropdown').dropdown();

// $('#comments').on('click', function(){
// 	$('.ui.dropdown').dropdown();
// });

$('.ui.dropdown').dropdown({on: 'click'});

	</script>