<?php
if (!empty($data['languages'])) {
    echo '<span id="acym__unsubscribe__label__language">'.acym_translation('ACYM_LANGUAGE').'</span>';
    echo acym_select(
        $data['languages'],
        'language',
        $data['lang'],
        null,
        'value',
        'text',
        'acym__unsubscribe__language__select'
    );
    ?>
	<script>
        const select = document.getElementById('acym__unsubscribe__language__select');
        let link = '<?php echo acym_currentURL();?>';

        const languageParam = link.match(/&language=[^&]+/);
        if (null !== languageParam && languageParam.length > 0) {
            link = link.replace(languageParam[0], '');
        }

        select.addEventListener('change', function () {
            link += '&language=' + this.value;
            window.location.href = link;
        });
	</script>
    <?php
}
?>
<form action="<?php echo acym_frontendLink('frontusers'); ?>"
	  name="unsubscribepage"
	  onsubmit="this.querySelector('#acym__save');"
	  class="acym_front_page acym_front_page__unsubscribe margin-top-2">
	<fieldset>
		<legend>
            <?php
            $title = acym_escape($this->config->get('unsubscribe_title'));
            if (!empty($title)) {
                echo $title;
            } else {
                echo acym_translation('ACYM_YOUR_NEWSLETTER_SUBSCRIPTIONS');
            }
            ?>
		</legend>
		<h2 class="margin-top-2 acym_front_page__unsubscribe__title">
            <?php echo acym_translation('ACYM_HERE_LISTS_YOU_ARE_SUBSCRIBED_TO'); ?>
		</h2>
		<div class="acym_front_page__unsubscribe__lists__container">
            <?php
            if (empty($data['subscriptions'])) {
                echo acym_translation('ACYM_NO_DATA_TO_DISPLAY');
                echo '</div>';
            } else {
            echo '<ul>';
            foreach ($data['subscriptions'] as $list) {
                if (empty($list->visible)) continue;
                echo '<li><input style="display: inline-block" id="list__'.$list->id.'" type="checkbox" name="lists['.$list->id.']" '.(empty($list->status) ? ''
                        : 'checked').'><label style="display: inline-block" for="list__'.$list->id.'">'.(!empty($list->display_name) ? $list->display_name
                        : $list->name).'</label></li>';
            }
            echo '</ul>';
            ?>
		</div>
		<div class="margin-top-1">
			<h2 class="acym_front_page__unsubscribe__title"><?php echo acym_translation('ACYM_WHY_ARE_YOU_UNSUBSCRIBING'); ?></h2>
            <?php
            if (!empty($data['surveyAnswers'])) {
                echo acym_select(
                    $data['surveyAnswers'],
                    'unsubscribe_selector_reason'
                );
                echo '<input type="text" id="acym__custom__unsubscribe__reason" name="unsubscribe_custom_reason" class="is-hidden margin-top-1">';
                echo '<input type="hidden" name="unsubscribe_reason">';
            } else {
                echo '<input type="text" name="unsubscribe_reason">';
            }
            ?>
		</div>
		<div class="acym_front_page__unsubscribe__lists__actions grid-x margin-top-1 align-center">
			<button type="button" class="button margin-right-1" id="acym__save" onclick="return acymSubmitForm('saveSubscriptions', this);">
                <?php echo acym_translation('ACYM_UPDATE'); ?>
			</button>
			<button type="button" class="button button-secondary" id="acym__unsub__all" onclick="return acymSubmitForm('unsubscribeAll', this);">
                <?php echo acym_translation('ACYM_UNSUBSCRIBE_ALL'); ?>
			</button>
		</div>
        <?php
        } ?>
	</fieldset>
    <?php acym_formOptions(); ?>
	<input type="hidden" name="user_id" value="<?php echo $data['user']->id; ?>">
	<input type="hidden" name="user_key" value="<?php echo acym_escape($data['user']->key); ?>">
	<input type="hidden" name="mail_id" value="<?php echo $data['mail_id']; ?>">
	<input type="hidden" name="displayed_checked_lists" id="displayed_checked_lists">
</form>
<?php if ('wordpress' == ACYM_CMS) exit; ?>
