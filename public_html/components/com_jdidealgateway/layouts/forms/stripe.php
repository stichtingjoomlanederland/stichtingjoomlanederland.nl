<?php
/**
 * @package    JDiDEAL
 *
 * @author     Roland Dalmulder <contact@rolandd.com>
 * @copyright  Copyright (C) 2009 - 2023 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Jdideal\Gateway;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

/**
 * @var array   $displayData The main array with data for display
 * @var Gateway $jdideal     The Gateway instance
 * @var array   $data        The payment details
 * @var string  $root        The base URL of the website
 * @var array   $output      The form data from the Stripe class
 */
extract($displayData);

HTMLHelper::script('https://js.stripe.com/v3/');
?>
<script type="module">
  const stripe = Stripe('<?php echo $jdideal->get('publishableKey'); ?>')

  const options = {
    clientSecret: '<?php echo $output['intent']->client_secret; ?>',
    // Fully customizable with appearance API.
    appearance: {/*...*/ },
  }

  // Set up Stripe.js and Elements to use in checkout form, passing the client secret obtained in step 2
  const elements = stripe.elements(options)

  // Create and mount the Payment Element
  const paymentElement = elements.create('payment')
  paymentElement.mount('#payment-element')

  const form = document.getElementById('payment-form')

  form.addEventListener('submit', async (event) => {
    event.preventDefault()

    const { error } = await stripe.confirmPayment({
      //`Elements` instance that was used to create the Payment Element
      elements,
      confirmParams: {
        return_url: '<?php echo $output['returnUrl']; ?>',
      },
    })

    if (error) {
      // This point will only be reached if there is an immediate error when
      // confirming the payment. Show error to your customer (for example, payment
      // details incomplete)
      const messageContainer = document.querySelector('#error-message')
      messageContainer.textContent = error.message
    } else {
      // Your customer will be redirected to your `return_url`. For some payment
      // methods like iDEAL, your customer will be redirected to an intermediate
      // site first to authorize the payment, then redirected to the `return_url`.
    }
  })
</script>
<form id="payment-form" data-secret="<?php
echo $intent->client_secret; ?>">
	<div id="payment-element">
		<!-- Elements will create form elements here -->
	</div>
	<button id="submit">Submit</button>
</form>
