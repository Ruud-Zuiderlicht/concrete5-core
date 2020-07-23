<?php /** @noinspection DuplicatedCode */

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Controller\Dialog\Page\Location;
use Concrete\Core\Entity\Page\PagePath;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Html\Service\Navigation;
use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application;

/** @var bool $isHome */
/** @var Location $controller */
/** @var Page $c */
/** @var PagePath $autoGeneratedPath */
/** @var PagePath[] $paths */

$app = Application::getFacadeApplication();
/** @var Form $form */
$form = $app->make(Form::class);
/** @var Navigation $navHelper */
$navHelper = $app->make(Navigation::class);

?>
<section class="ccm-ui">
    <header>
        <h3>
            <?php echo t('Locations') ?>
        </h3>
    </header>

    <div class="ccm-page-panel-locations row">
        <div class="col-sm-12">
            <form method="post" action="<?php echo $controller->action('submit') ?>" data-dialog-form="location"
                  data-panel-detail-form="location">

                <?php echo $form->hidden("cParentID", $cParentID); ?>

                <?php if ($c->isGeneratedCollection() || $c->isPageDraft()) { ?>
                    <h3 class="font-weight-light">
                        <?php echo t('Current Canonical URL') ?>
                    </h3>

                    <div class="breadcrumb">
                        <?php if ($c->isPageDraft()) { ?>
                            <?php echo t('None. Pages do not have canonical URLs until they are published.') ?>
                        <?php } else { ?>
                            <?php /** @noinspection PhpParamsInspection */
                            echo $navHelper->getLinkToCollection($c) ?>
                        <?php } ?>
                    </div>

                <?php } else { ?>
                    <h5 class="mt-3">
                        <?php echo t('URLs to this Page') ?>
                    </h5>

                    <table class="mt-4 ccm-page-panel-detail-location-paths">
                        <thead>
                        <tr>
                            <?php if (!$isHome) { ?>
                                <th>
                                    <?php echo t('Canonical') ?>
                                </th>
                            <?php } ?>

                            <th style="width: 100%">
                                <?php echo t('Path') ?>
                            </th>

                            <th></th>
                        </tr>
                        </thead>

                        <tbody></tbody>
                    </table>

                    <button class="btn btn-secondary float-right mt-1 mb-2" type="button" data-action="add-url">
                        <?php echo t('Add URL') ?>
                    </button>

                    <div class="clearfix"></div>

                    <p class="text-right">
                        <small class="text-muted">
                            <?php echo t('Note: Additional page paths are not versioned.<br> They will be available immediately.') ?>
                        </small>
                    </p>

                <?php } ?>

                <?php if (isset($sitemap) && $sitemap) { ?>
                    <input type="hidden" name="sitemap" value="1"/>
                <?php } ?>
            </form>
        </div>
    </div>
</section>

<style type="text/css">
    table.ccm-page-panel-detail-location-paths td {
        vertical-align: middle !important;
    }
</style>

<script type="text/template" id="pagePath-template">
    <tr>
        <% if (!isHome) { %>
        <td style="text-align: center">
            <!--suppress HtmlFormInputWithoutLabel -->
            <input type="radio" name="canonical" value="<%=row%>" <% if (isCanonical) { %>checked<% } %> />
        </td>
        <% } %>

        <td>
            <div class="input-group">
                <% if (isAutoGenerated) { %>
                <input type="hidden" name="generated" value="<%=row%>">
                <input type="hidden" name="path[<%=row%>]" value="<%=pagePath%>">
                <% } %>

                <!--suppress HtmlFormInputWithoutLabel -->
                <input type="text" data-input="auto" class="form-control border-right-0" <% if (isAutoGenerated) {
                %>disabled<% } else { %>name="path[]"<% } %> value="<%=pagePath%>" />

                <div class="input-group-append">
                    <span class="input-group-icon border-left-0  <% if (isAutoGenerated) { %>disabled<% } %>">
                        <i class="fas fa-link"></i>
                    </span>
                </div>
            </div>
        </td>

        <td>
            <% if (!isAutoGenerated) { %>
            <a href="#" data-action="remove-page-path" class="icon-link"><i class="far fa-trash-alt"></i></a>
            <% } %>
        </td>
    </tr>
</script>

<div class="ccm-panel-detail-form-actions dialog-buttons">
    <button class="float-left btn btn-secondary" type="button" data-dialog-action="cancel"
            data-panel-detail-action="cancel">
        <?php echo t('Cancel') ?>
    </button>

    <button class="float-right btn btn-success" type="button" data-dialog-action="submit"
            data-panel-detail-action="submit">
        <?php echo t('Save Changes') ?>
    </button>
</div>

<!--suppress JSJQueryEfficiency, ES6ConvertVarToLetConst -->
<script type="text/javascript">
    var renderPagePath = _.template(
        $('script#pagePath-template').html()
    );

    $(function () {
        $('form[data-panel-detail-form=location]').on('click', 'a[data-action=remove-page-path]', function (e) {
            e.preventDefault();
            $(this).closest('tbody').find('input[type=radio]:first').prop('checked', true);
            $(this).closest('tr').remove();
        });

        $('button[data-action=add-url]').on('click', function () {
            var rows = $('table.ccm-page-panel-detail-location-paths tbody tr').length;
            $('table.ccm-page-panel-detail-location-paths tbody').append(
                renderPagePath({
                    isAutoGenerated: false,
                    isCanonical: false,
                    isHome: <?php echo intval($isHome)?>,
                    pagePath: '',
                    row: rows
                })
            );
        });

        // first, we render the URL as it would be displayed auto-generated
        $('table.ccm-page-panel-detail-location-paths tbody').append(
            renderPagePath({
                isAutoGenerated: <?php echo intval($autoGeneratedPath->isPagePathAutoGenerated())?>,
                isCanonical: <?php echo intval($autoGeneratedPath->isPagePathCanonical())?>,
                isHome: <?php echo intval($isHome)?>,
                pagePath: '<?php echo $autoGeneratedPath->getPagePath()?>',
                row: 0
            })
        );

        // now we loop through all the rest of the page paths
        <?php  foreach ($paths as $i => $path) { ?>
        $('table.ccm-page-panel-detail-location-paths tbody').append(
            renderPagePath({
                isAutoGenerated: <?php echo intval($path->isPagePathAutoGenerated())?>,
                isCanonical: <?php echo intval($path->isPagePathCanonical())?>,
                isHome: <?php echo intval($isHome)?>,
                pagePath: '<?php /** @noinspection RegExpRedundantEscape */echo preg_replace("/['\"\(\)\{\}\s]/", '', $path->getPagePath())?>',
                row: <?php echo $i + 1?>
            })
        );
        <?php } ?>
    });
</script>