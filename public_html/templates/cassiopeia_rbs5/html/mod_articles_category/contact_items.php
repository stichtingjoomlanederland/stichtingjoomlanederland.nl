<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_category
 *
 * @copyright   (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
// use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
?>
<div class="row justify-content-center">
<?php foreach ($items as $index => $item) : ?>
    <div class="col-lg-4 col-sm-6 col-12 mt-4 pt-2">
        <div class="team text-center rounded p-3 pt-4">
            <?php
            $images = json_decode($item->images);
            if ($images->image_intro) {
                echo LayoutHelper::render(
                'rbs5.responsive_image', [
                    'src' => $images->image_intro,
                    'class' => 'img-fluid avatar avatar-medium shadow rounded-pill',
                    'alt' => $images->image_intro_alt,
                    'sizes' => ['600x600', '400x400'], // 1:1
                ]);
            }

            // Get the custom fields from the item = article object
            $jcfields = FieldsHelper::getFields('com_content.article', $item, true); //($item is the full object, not the ID)
            // Populate the Custom Fields array to get data
            $fieldsByName = \Joomla\Utilities\ArrayHelper::pivot($jcfields, 'name');
            ?>
            <div class="content mt-3">
                <h3 class="title mb-0">
                    <?php if ($params->get('link_titles') == 1) : ?>
                        <?php $attributes = ['class' => 'mod-articles-category-title ' . $item->active]; ?>
                        <?php $link = htmlspecialchars($item->link, ENT_COMPAT, 'UTF-8', false); ?>
                        <?php $title = htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false); ?>
                        <?php echo HTMLHelper::_('link', $link, $title, $attributes); ?>
                    <?php else : ?>
                        <?php echo $item->title; ?>
                    <?php endif; ?>
                </h3>
                <?php if ($params->get('show_introtext')) : ?>
                    <small class="text-muted"><?php echo $item->displayIntrotext; ?></small>
                <?php endif; ?>
                <ul class="list-unstyled mt-3 social-icon social mb-0">

                    <?php if ($fieldsByName['facebook']->rawvalue) : ?>
                        <li class="list-inline-item">
                            <a href="<?php echo $fieldsByName['facebook']->rawvalue; ?>" class="rounded">
                                <i class="fab fa-facebook" title="Facebook"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($fieldsByName['linkedin']->rawvalue) : ?>
                        <li class="list-inline-item">
                            <a href="<?php echo $fieldsByName['linkedin']->rawvalue; ?>" class="rounded">
                                <i class="fab fa-linkedin-in" title="Linkedin"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($fieldsByName['twitter']->rawvalue) : ?>
                        <li class="list-inline-item">
                            <a href="<?php echo $fieldsByName['twitter']->rawvalue; ?>" class="rounded">
                                <i class="fab fa-twitter" title="Twitter"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($fieldsByName['e-mail']->value) : ?>
                        <li class="list-inline-item">
                            <a href="mailto:<?php echo $fieldsByName['e-mail']->value; ?>" class="rounded">
                                <i class="fas fa-envelope" title="Envelope"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                </ul><!--end icon-->
            </div>
        </div>
    </div><!--end col-->
<?php /* ?>
    <?php if ($item->displayHits) : ?>
        <span class="mod-articles-category-hits">
            (<?php echo $item->displayHits; ?>)
        </span>
    <?php endif; ?>

    <?php if ($params->get('show_author')) : ?>
        <span class="mod-articles-category-writtenby">
            <?php echo $item->displayAuthorName; ?>
        </span>
    <?php endif; ?>

    <?php if ($item->displayCategoryTitle) : ?>
        <span class="mod-articles-category-category">
            (<?php echo $item->displayCategoryTitle; ?>)
        </span>
    <?php endif; ?>

    <?php if ($item->displayDate) : ?>
        <span class="mod-articles-category-date"><?php echo $item->displayDate; ?></span>
    <?php endif; ?>

    <?php if ($params->get('show_tags', 0) && $item->tags->itemTags) : ?>
        <div class="mod-articles-category-tags">
            <?php echo LayoutHelper::render('joomla.content.tags', $item->tags->itemTags); ?>
        </div>
    <?php endif; ?>

    <?php if ($params->get('show_introtext')) : ?>
        <p class="mod-articles-category-introtext">
            <?php echo $item->displayIntrotext; ?>
        </p>
    <?php endif; ?>

    <?php if ($params->get('show_readmore')) : ?>
        <p class="mod-articles-category-readmore">
            <a class="mod-articles-category-title <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
                <?php if ($item->params->get('access-view') == false) : ?>
                    <?php echo Text::_('MOD_ARTICLES_CATEGORY_REGISTER_TO_READ_MORE'); ?>
                <?php elseif ($item->alternative_readmore) : ?>
                    <?php echo $item->alternative_readmore; ?>
                    <?php echo HTMLHelper::_('string.truncate', $item->title, $params->get('readmore_limit')); ?>
                        <?php if ($params->get('show_readmore_title', 0)) : ?>
                            <?php echo HTMLHelper::_('string.truncate', $item->title, $params->get('readmore_limit')); ?>
                        <?php endif; ?>
                <?php elseif ($params->get('show_readmore_title', 0)) : ?>
                    <?php echo Text::_('MOD_ARTICLES_CATEGORY_READ_MORE'); ?>
                    <?php echo HTMLHelper::_('string.truncate', $item->title, $params->get('readmore_limit')); ?>
                <?php else : ?>
                    <?php echo Text::_('MOD_ARTICLES_CATEGORY_READ_MORE_TITLE'); ?>
                <?php endif; ?>
            </a>
        </p>
    <?php endif; ?>
    <?php */ ?>
<?php endforeach; ?>
</div>