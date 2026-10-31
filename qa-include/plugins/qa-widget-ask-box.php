<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-include/qa-widget-ask-box.php
	Description: Widget module class for ask a question box


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_ask_box
{
	public function allow_template($template)
	{
		$allowed = array(
			'activity', 'categories', 'custom', 'feedback', 'qa', 'questions',
			'hot', 'search', 'tag', 'tags', 'unanswered'
		);
		return in_array($template, $allowed);
	}

	public function allow_region($region)
	{
		return in_array($region, array('main', 'side', 'full'));
	}

	public function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
	{
		if (isset($qa_content['categoryids']))
			$params=array('cat' => end($qa_content['categoryids']));
		else
			$params=null;
?>
<div class="qa-ask-box">
	 <div class="qam-search" id="the-top-search" style="width:49%; float:right; height:36px;">
			<div class="qa-search">
						<form method="get" action="./search">
						<input type="text" name="q" placeholder="Sitede Arama Yap.." value="" class="qa-search-field" style="float:left; width:85%; padding-right:0px; text-align:center; border: 1px solid #e5e5e5; border-top-left-radius:3px; border-bottom-left-radius:3px;">
						<input type="submit" value="Ara" class="qa-search-button" style="float:right; background-color: #fff; border:none; width:15%">
				</form>
			</div>
		</div>
	<form method="post" action="<?php echo qa_path_html('ask', $params); ?>">
		<table class="qa-form-tall-table" style="width:50%; float:left">
			<tr style="vertical-align:middle;">
<?php
			if ($region=='side') {
?>
			</tr>
			<tr>
<?php
			}
?>
				<td class="qa-form-tall-data" style="" width="*">
					<input type="submit" value="Sor" class="qa-search-button" style="width:20%; text-align:center; float:left; background: none; border:none; font-weight:bold!important; color:#797979!important">
					<input name="title" type="text" class="qa-form-tall-text" style="width:80%!important; text-align:center; float:left" placeholder="Buraya Sorunu Sor!">
				</td>
			</tr>
		</table>
		<input type="hidden" name="doask1" value="1">
	</form>
</div>
<?php
	}
}
