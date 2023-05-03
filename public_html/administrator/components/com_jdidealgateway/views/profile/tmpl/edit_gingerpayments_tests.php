<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var JdidealgatewayViewProfile $this */

?>
<div class="control-group">
	<label for="statusTest">
		<h3><?php
			echo Text::_('COM_ROPAYMENTS_TEST_PAYMENT_STATUS'); ?></h3>
	</label>
	<select id="statusTest">
		<option value="1"><?php
			echo Text::_('COM_ROPAYMENTS_TEST_1_EURO'); ?></option>
		<option value="2"><?php
			echo Text::_('COM_ROPAYMENTS_TEST_2_EURO'); ?></option>
		<option value="3"><?php
			echo Text::_('COM_ROPAYMENTS_TEST_3_EURO'); ?></option>
		<option value="4"><?php
			echo Text::_('COM_ROPAYMENTS_TEST_4_EURO'); ?></option>
		<option value="5"><?php
			echo Text::_('COM_ROPAYMENTS_TEST_5_EURO'); ?></option>
		<option value="6"><?php
			echo Text::_('COM_ROPAYMENTS_TEST_6_EURO'); ?></option>
		<option value="7"><?php
			echo Text::_('COM_ROPAYMENTS_TEST_7_EURO'); ?></option>
	</select>
	<button id="runTest" class="btn btn-primary">
		<?php
		echo Text::_('COM_ROPAYMENTS_RUN_TEST'); ?>
	</button>
</div>
<div class="control-group">
	<div>
		<h3><?php
			echo Text::_('COM_ROPAYMENTS_TEST_RESULT'); ?></h3>
	</div>
	<div id="resultTest"></div>
</div>
<script type="module">
  const button = document.getElementById('runTest')
  const result = document.getElementById('resultTest')
  button.addEventListener('click', () => {
    const amount = document.getElementById('statusTest').value
    const profileAlias = document.getElementById('jform_alias').value
    const link = `/index.php?option=com_jdidealgateway&task=ajax.paymentTest&format=json&amount=${amount}&profileAlias=${profileAlias}`
    fetch(link, { headers: { 'Content-Type': 'application/json; charset=utf-8' } })
      .then(res => res.json()) // parse response as JSON (can be res.text() for plain response)
      .then(response => {
        if (!response.success) {
          result.innerHTML = response.message
        }

        if (response.success) {
          result.innerHTML = response.data
        }
      })
      .catch(error => {
        result.innerHTML = error.value
      })
  })
</script>
