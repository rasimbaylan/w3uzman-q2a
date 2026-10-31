<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/mouseover-layer/qa-mouseover-admin-form.php
	Description: Generic module class for mouseover layer plugin to provide admin form and default option


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

class qa_mouseover_admin_form
{
	public function option_default($option)
	{
		if ($option === 'mouseover_content_max_len')
			return 480;
	}

	private function preview_length($value)
	{
		return is_numeric($value) ? max(40, min(2000, (int)$value)) : 480;
	}

	public function admin_form(&$qa_content)
	{
		$saved = qa_clicked('mouseover_save_button');

		if ($saved) {
			qa_opt('mouseover_content_on', (int) qa_post_text('mouseover_content_on_field'));
			qa_opt('mouseover_content_max_len', $this->preview_length(qa_post_text('mouseover_content_max_len_field')));
		}

		qa_set_display_rules($qa_content, array(
			'mouseover_content_max_len_display' => 'mouseover_content_on_field',
		));

		return array(
			'ok' => $saved ? 'Haber önizleme ayarları kaydedildi.' : null,

			'fields' => array(
				array(
					'label' => 'Haber listelerinde başlığın üzerine gelindiğinde içerik önizlemesini göster',
					'type' => 'checkbox',
					'value' => qa_opt('mouseover_content_on'),
					'tags' => 'name="mouseover_content_on_field" id="mouseover_content_on_field"',
				),

				array(
					'id' => 'mouseover_content_max_len_display',
					'label' => 'Önizlemenin en fazla uzunluğu:',
					'suffix' => 'karakter (40–2000)',
					'type' => 'number',
					'value' => $this->preview_length(qa_opt('mouseover_content_max_len')),
					'tags' => 'name="mouseover_content_max_len_field" min="40" max="2000"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Değişiklikleri kaydet',
					'tags' => 'name="mouseover_save_button"',
				),
			),
		);
	}
}
