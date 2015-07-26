	<div id="center">	
	   <div class="container_box1">
	   <div class="header">Members</div>
	   <?php
	   include_once($setting['sitepath'].'/includes/pagination.class.php');
	   $setting['gperpage'] = ($setting['gperpage']<1?15:$setting['gperpage']);
	   $result = yasDB_select("SELECT count(id) FROM `user` ");
	   $query_data = $result->fetch_array(MYSQLI_NUM);
	   $numrows = $query_data[0];
	   $rquery = yasDB_select("SELECT `username` FROM `user` ORDER BY `plays` DESC",false);
	   $ranking = array();
	   $i=0;
	   while ($cow = $rquery->fetch_array(MYSQLI_ASSOC)) {
		   $ranking[$i] = $cow['username'];
		   $i++;
	   }
	   $pageurl = new pagination($numrows, $setting['seo'], 'members', $setting['gperpage'], 3);
	   $query = yasDB_select("SELECT * FROM user ORDER BY `username` asc LIMIT " . $pageurl->start . ", " . $pageurl->limit);
	   while ($row = $query->fetch_array(MYSQLI_ASSOC)) {
		   $rank = array_search($row['username'],$ranking)+1;
		   $id = $row['id'];
		   $username = $row['username'];
		   $plays = $row['plays'];
		   if ($setting['seo'] == 'yes') {
			   $memberlink = $setting['siteurl'].'showmember/'.$id.'.html';
		   } else {
			   $memberlink = $setting['siteurl'] . 'index.php?act=showmember&id='.$id ;
		   }
		   if ( $row['useavatar'] == '1' ) {
			   $avatarimage = $setting['siteurl'] . 'avatars/' . $row['avatarfile'];
		   } else {
			   $avatarimage = $setting['siteurl'] . 'avatars/useruploads/noavatar.JPG';
		   }
		   ?>
		   <div class="inner_box">
		   <div class="images">
		   <a href="<?php echo $memberlink;?>"><img src="<?php echo $avatarimage;?>" height="100" width="130" align="left" title="<?php echo $username;?>"></a>
		   </div><div class="clear"></div>
		   <div class="text2"><a href="<?php echo $memberlink;?>"><?php echo $username;?></a></div><div style="text-align:center;">Rank: <?php echo $rank;?>&nbsp;|&nbsp;Plays: <?php echo $plays;?></div>
		   <div class="clear"></div></div>
		   <?php
	   }?>
	   <div id="page_box"><?php
	   echo $pageurl->showPagination();
?>
</div>
<div class="clear"></div>
</div>