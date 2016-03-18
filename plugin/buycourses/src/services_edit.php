<?php
/* For license terms, see /license.txt */
/**
 * Create new Services for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
use Doctrine\Common\Collections\Criteria;

$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$serviceId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;

if (!$serviceId) {
    header('Location: configuration.php');
}

$plugin = BuyCoursesPlugin::create();
$currency = $plugin->getSelectedCurrency();
$em = Database::getManager();
$users = $em->getRepository('ChamiloUserBundle:User')->findAll();
$userOptions = [];
if (!empty($users)) {
    foreach ($users as $user) {
        $userOptions[$user->getId()] = $user->getCompleteNameWithUsername();
    }
}

api_protect_admin_script(true);
$htmlHeadXtra[] = '<link  href="'. api_get_path(WEB_PATH) .'web/assets/cropper/dist/cropper.min.css" rel="stylesheet">';
$htmlHeadXtra[] = '<script src="'. api_get_path(WEB_PATH) .'web/assets/cropper/dist/cropper.min.js"></script>';
$htmlHeadXtra[] = '<script>
$(document).ready(function() {
    var $image = $("#previewImage");
    var $input = $("[name=\'cropResult\']");
    var $cropButton = $("#cropButton");
    var canvas = "";
    var imageWidth = "";
    var imageHeight = "";
    
    $("input:file").change(function() {
        var oFReader = new FileReader();
        oFReader.readAsDataURL(document.getElementById("picture_form").files[0]);

        oFReader.onload = function (oFREvent) {
            $image.attr("src", this.result);
            $("#labelCropImage").html("'.get_lang('Preview').'");
            $("#cropImage").addClass("thumbnail");
            $cropButton.removeClass("hidden");
            // Destroy cropper
            $image.cropper("destroy");

            $image.cropper({
                aspectRatio: 4 / 3,
                responsive : true,
                center : false,
                guides : false,
                movable: false,
                zoomable: false,
                rotatable: false,
                scalable: false,
                crop: function(e) {
                    // Output the result data for cropping image.
                    $input.val(e.x+","+e.y+","+e.width+","+e.height);
                }
            });
        };
    });
    
    $("#cropButton").on("click", function() {
        var canvas = $image.cropper("getCroppedCanvas");
        var dataUrl = canvas.toDataURL();
        $image.attr("src", dataUrl);
        $input.val(dataUrl);
        $image.cropper("destroy");
        $cropButton.addClass("hidden");
        return false;
    });
});

</script>';

//view
$interbreadcrumb[] = [
    'url' => 'configuration.php',
    'name' => $plugin->get_lang('AvailableCourses')
];

$service = $plugin->getServices($serviceId);

$formDefaultValues = [
    'name' => $service['name'],
    'description' => $service['description'],
    'price' => $service['price'],
    'duration_days' => $service['duration_days'],
    'owner_id' => intval($service['owner_id']),
    'applies_to' => intval($service['applies_to']),
    'max_subscribers' => intval($service['max_subscribers']),
    'renewable' => ($service['renewable'] == 1) ? true : false,
    'visibility' => ($service['visibility'] == 1) ? true : false,
    'image' =>
    is_file(api_get_path(SYS_PLUGIN_PATH).'buycourses/uploads/services/images/simg-'.$serviceId.'.png')
        ?
    api_get_path(WEB_PLUGIN_PATH).'buycourses/uploads/services/images/simg-'.$serviceId.'.png'
        :
    api_get_path(WEB_CODE_PATH).'img/session_default.png',
    'video_url' => $service['video_url'],
    'service_information' => $service['service_information']
];

$form = new FormValidator('Services');
$form->addText('name', $plugin->get_lang('ServiceName'));
$form->addTextarea('description', $plugin->get_lang('Description'));
$form->addElement(
    'number',
    'price',
    [$plugin->get_lang('Price'), null, $currency['iso_code']],
    ['step' => 0.01]
);
$form->addElement(
    'number',
    'duration_days',
    [$plugin->get_lang('Duration'), null, get_lang('Days')],
    ['step' => 1]
);
$form->addCheckBox('renewable', $plugin->get_lang('Renewable'));
$form->addElement(
    'radio',
    'applies_to',
    $plugin->get_lang('AppliesTo'),
    get_lang('None'),
    0
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('User'),
    1
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('Course'),
    2
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('Session'),
    3
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    $plugin->get_lang('SubscriptionPackage'),
    4
);
$form->addElement(
    'number',
    'max_subscribers',
    [null, $plugin->get_lang('EnterTheMaxSubscribersForThisService')],
    ['step' => 1, 'value' => 0]
);
$form->addSelect(
    'owner_id',
    get_lang('Owner'),
    $userOptions
);
$form->addCheckBox('visibility', $plugin->get_lang('VisibleInCatalog'));
$form->addHtml(''
            . '<div class="form-group">'
                . '<label for="currentImage" id="labelCurrentImage" class="col-sm-2 control-label">'.  get_lang('Image').'</label>'
                    . '<div class="col-sm-8">'
                        . '<div id="currentImage" name="currentImage" class="cropCanvas thumbnail">'
                            . '<img id="previewCurrentImage" src="'.$formDefaultValues['image'].'" >'
                        . '</div>'
                    . '</div>'
            . '</div>'
. '');
$form->addElement(
        'file',
        'picture',
        get_lang('UpdateImage'),
        array('id' => 'picture_form', 'class' => 'picture-form')
    );
$form->addHtml(''
            . '<div class="form-group">'
                . '<label for="cropImage" id="labelCropImage" class="col-sm-2 control-label"></label>'
                    . '<div class="col-sm-8">'
                        . '<div id="cropImage" class="cropCanvas">'
                            . '<img id="previewImage" >'
                        . '</div>'
                        . '<div>'
                            . '<button class="btn btn-primary hidden" name="cropButton" id="cropButton"><em class="fa fa-crop"></em> '.get_lang('CropYourPicture').'</button>'
                        . '</div>'
                    . '</div>'
            . '</div>'
. '');
$form->addHidden('cropResult', '');
$form->addText('video_url', get_lang('VideoUrl'), false);
$form->addHtmlEditor('service_information', get_lang('ServiceInformation'), false);
$form->addHidden('id', $serviceId);
$form->addButtonSave(get_lang('Edit'));
$form->setDefaults($formDefaultValues);
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $plugin->updateService($values, $serviceId);

    header('Location: configuration.php');
}


$htmlHeadXtra[] = '
<script>
    $(document).ready(function() {
        if ($("input[name=\"applies_to\"]:checked").val() == 4) {
            $("input[name=\"max_subscribers\"]").attr("type", "number");
            $(".help-block").show();
        } else {
            $("input[name=\"max_subscribers\"]").attr("type", "hidden");
            $(".help-block").hide();
        }
        $("input[name=\"applies_to\"]").click(function() {
            if ($("input[name=\"applies_to\"]:checked").val() == 4) {
                $("input[name=\"max_subscribers\"]").attr("type", "number");
                $(".help-block").show();
            } else {
                $("input[name=\"max_subscribers\"]").attr("type", "hidden");
                $(".help-block").hide();
            }
        });
    });
</script>';


$templateName = $plugin->get_lang('EditService');
$tpl = new Template($templateName);

$tpl->assign('header', $templateName);
$tpl->assign('content', $form->returnForm());
$tpl->display_one_col_template();
