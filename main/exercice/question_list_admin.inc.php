<?php
/* For licensing terms, see /license.txt */

/**
 *	Code library for HotPotatoes integration.
 *	@package chamilo.exercise
 *	@author Olivier Brouckaert & Julio Montoya & Hubert Borderiou 21-10-2011 (Question by category)

 *	QUESTION LIST ADMINISTRATION
 *
 *	This script allows to manage the question list
 *	It is included from the script admin.php
 */

// deletes a question from the exercise (not from the data base)
if ($deleteQuestion) {
    // if the question exists
    if ($objQuestionTmp = Question::read($deleteQuestion)) {
        $objQuestionTmp->delete($exerciseId);

        // if the question has been removed from the exercise
        if ($objExercise->removeFromList($deleteQuestion)) {
            $nbrQuestions--;
        }
    }
    // destruction of the Question object
    unset($objQuestionTmp);
}
$ajax_url = api_get_path(WEB_AJAX_PATH)."exercise.ajax.php?".api_get_cidreq()."&exercise_id=".intval($exerciseId);
?>
    <style>
        .ui-state-highlight { height: 30px; line-height: 1.2em; }
        /*Fixes edition buttons*/
        .ui-accordion-icons .ui-accordion-header .edition a {
            padding-left:4px;
        }
    </style>

    <div id="dialog-confirm" title="<?php echo get_lang("ConfirmYourChoice"); ?>" style="display:none;">
        <p>
        <span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0; display:none;">
        </span>
            <?php echo get_lang("AreYouSureToDelete"); ?>
        </p>
    </div>

    <script>
        $(function() {
            $( "#dialog:ui-dialog" ).dialog( "destroy" );
            $( "#dialog-confirm" ).dialog({
                autoOpen: false,
                show: "blind",
                resizable: false,
                height:150,
                modal: false
            });

            $(".opener").click(function() {
                var targetUrl = $(this).attr("href");
                $( "#dialog-confirm" ).dialog({
                    modal: true,
                    buttons: {
                        "<?php echo get_lang("Yes"); ?>": function() {
                            location.href = targetUrl;
                            $( this ).dialog( "close" );

                        },
                        "<?php echo get_lang("No"); ?>": function() {
                            $( this ).dialog( "close" );
                        }
                    }
                });
                $( "#dialog-confirm" ).dialog("open");
                return false;
            });

            var stop = false;
            $( "#question_list h3" ).click(function( event ) {
                if ( stop ) {
                    event.stopImmediatePropagation();
                    event.preventDefault();
                    stop = false;
                }
            });

            var icons = {
                header: "ui-icon-circle-arrow-e",
                headerSelected: "ui-icon-circle-arrow-s"
            };

            /* We can add links in the accordion header */
            $("div > div > div > .edition > div > a").click(function() {
                //Avoid the redirecto when selecting the delete button
                if (this.id.indexOf('delete') == -1) {
                    newWind = window.open(this.href,"_self");
                    newWind.focus();
                    return false;
                }
            });

            $( "#question_list" ).accordion({
                icons: icons,
                heightStyle: "content",
                active: false, // all items closed by default
                collapsible: true,
                header: ".header_operations"
            })
            .sortable({
                cursor: "move", // works?
                update: function(event, ui) {
                    var order = $(this).sortable("serialize") + "&a=update_question_order&exercise_id=<?php echo intval($_GET['exerciseId']);?>";
                    $.post("<?php echo $ajax_url ?>", order, function(reponse){
                        $("#message").html(reponse);
                    });
                },
                axis: "y",
                placeholder: "ui-state-highlight", //defines the yellow highlight
                handle: ".moved", //only the class "moved"
                stop: function() {
                    stop = true;
                }
            });
        });
    </script>
<?php

//we filter the type of questions we can add
Question :: display_type_menu($objExercise);
echo '<div style="clear:both;"></div>';
echo '<div id="message"></div>';
$token = Security::get_token();
//deletes a session when using don't know question type (ugly fix)
unset($_SESSION['less_answer']);

// If we are in a test
$inATest = isset($exerciseId) && $exerciseId > 0;
if (!$inATest) {
    echo "<div class='alert alert-warning'>".get_lang("ChoiceQuestionType")."</div>";
} else {
    // Title line
    echo "<div class='table-responsive'>";
    echo "<table class='table table-condensed'>";
    echo "<tr>";
    echo "<th style=\"width: 50%;\">" .get_lang('Questions'). "</th>";
    echo "<th style=\"width: 6%;\">" .get_lang('Type'). "</th>";
    echo "<th style=\"width: 22%; text-align:center;\">" .get_lang('Category'). "</th>";
    echo "<th style=\"width: 6%;\">" .get_lang('Difficulty'). "</th>";
    echo "<th style=\"width: 16%; float:left;\">" .get_lang('Score'). "</th>";
    echo "</tr>";
    echo "</table>";
    echo "</div>";

    echo '<div id="question_list">';
    if ($nbrQuestions) {
        //Always getting list from DB
        $questionList = $objExercise->selectQuestionList(true);

        // Style for columns
        $styleQuestion = "width:50%; float:left; margin-left: 25px;";
        $styleType = "width:4%; float:left; text-align:center;";
        $styleCat = "width:22%; float:left; text-align:center;";
        $styleLevel = "width:6%; float:left; text-align:center;";
        $styleScore = "width:4%; float:left; text-align:center;";

        if (is_array($questionList)) {
            foreach ($questionList as $id) {
                //To avoid warning messages
                if (!is_numeric($id)) {
                    continue;
                }
                $objQuestionTmp = Question::read($id);
                $question_class = get_class($objQuestionTmp);

                $clone_link = '<a href="'.api_get_self().'?'.api_get_cidreq().'&clone_question='.$id.'">'.
                    Display::return_icon('cd.gif',get_lang('Copy'), array(), ICON_SIZE_SMALL).'</a>';
                $edit_link = ($objQuestionTmp->type == CALCULATED_ANSWER && $objQuestionTmp->isAnswered()) ?
                    '<a>'.Display::return_icon(
                        'edit_na.png',
                        get_lang('QuestionEditionNotAvailableBecauseItIsAlreadyAnsweredHoweverYouCanCopyItAndModifyTheCopy'),
                        array(),
                        ICON_SIZE_SMALL
                    ).'</a>' :
                    '<a href="'.api_get_self().'?'.api_get_cidreq().'&type='.
                    $objQuestionTmp->selectType().'&myid=1&editQuestion='.$id.'">'.
                    Display::return_icon(
                        'edit.png',
                        get_lang('Modify'),
                        array(),
                        ICON_SIZE_SMALL
                    ).'</a>';
                $delete_link = null;
                if ($objExercise->edit_exercise_in_lp == true) {
                    $delete_link = '<a id="delete_'.$id.'" class="opener"  href="'.api_get_self().'?'.api_get_cidreq().'&exerciseId='.$exerciseId.'&deleteQuestion='.$id.'" >'.Display::return_icon('delete.png',get_lang('RemoveFromTest'), array(), ICON_SIZE_SMALL).'</a>';
                }

                $edit_link = Display::tag('div', $edit_link,   array('style'=>'float:left; padding:0px; margin:0px'));
                $clone_link = Display::tag('div', $clone_link,  array('style'=>'float:left; padding:0px; margin:0px'));
                $delete_link = Display::tag('div', $delete_link, array('style'=>'float:left; padding:0px; margin:0px'));
                $actions = Display::tag(
                    'div',
                    $edit_link.$clone_link.$delete_link,
                    array('class'=>'edition','style'=>'width:100px; right:10px; margin-top: 8px; position: absolute; top: 10%;')
                );

                $title = Security::remove_XSS($objQuestionTmp->selectTitle());
                $move = Display::return_icon(
                    'all_directions.png',
                    get_lang('Move'),
                    array('class'=>'moved', 'style'=>'margin-bottom:-0.5em;')
                );

                // Question name
                $questionName = Display::tag(
                    'div',
                    '<a href="#" title = "'.Security::remove_XSS($title).'">'.$move.' '.cut($title, 42).'</a>',
                    array('style' => $styleQuestion)
                );

                // Question type
                list($typeImg, $typeExpl) = $objQuestionTmp->get_type_icon_html();
                $questionType = Display::tag('div', Display::return_icon($typeImg, $typeExpl, array(), ICON_SIZE_MEDIUM), array('style'=>$styleType));

                // Question category
                $txtQuestionCat = Security::remove_XSS(TestCategory::getCategoryNameForQuestion($objQuestionTmp->id));
                if (empty($txtQuestionCat)) {
                    $txtQuestionCat = "-";
                }
                $questionCategory = Display::tag('div', '<a href="#" style="padding:0px; margin:0px;" title="'.$txtQuestionCat.'">'.
                    cut($txtQuestionCat, 42).'</a>', array('style'=>$styleCat));

                // Question level
                $txtQuestionLevel = $objQuestionTmp->level;
                if (empty($objQuestionTmp->level)) {
                    $txtQuestionLevel = '-';
                }
                $questionLevel = Display::tag('div', $txtQuestionLevel, array('style'=>$styleLevel));

                // Question score
                $questionScore = Display::tag('div', $objQuestionTmp->selectWeighting(), array('style'=>$styleScore));

                echo '<div id="question_id_list_'.$id.'" >';
                echo '<div class="header_operations">';
                echo $questionName;
                echo $questionType;
                echo $questionCategory;
                echo $questionLevel;
                echo $questionScore;
                echo $actions;
                echo '</div>';
                echo '<div class="question-list-description-block">';
                echo '<p class="lead">' . get_lang($question_class) . '</p>';
                //echo get_lang('Level').': '.$objQuestionTmp->selectLevel();
                ExerciseLib::showQuestion($id, false, null, null, false, true, false, true, $objExercise->feedback_type, true);
                echo '</div>';
                echo '</div>';
                unset($objQuestionTmp);
            }
        }
    }

    if (!$nbrQuestions) {
        echo Display::display_warning_message(get_lang('NoQuestion'));
    }
    echo '</div>'; //question list div
}
