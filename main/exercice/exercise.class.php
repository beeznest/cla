<?php
/* For licensing terms, see /license.txt */

/**
 * Class Exercise
 *
 * Allows to instantiate an object of type Exercise
 * @package chamilo.exercise
 * @author Olivier Brouckaert
 * @author Julio Montoya Cleaning exercises
 * Modified by Hubert Borderiou #294
 */
class Exercise
{
    public $id;
    public $name;
    public $title;
    public $exercise;
    public $description;
    public $sound;
    public $type; //ALL_ON_ONE_PAGE or ONE_PER_PAGE
    public $random;
    public $random_answers;
    public $active;
    public $timeLimit;
    public $attempts;
    public $feedback_type;
    public $end_time;
    public $start_time;
    public $questionList;  // array with the list of this exercise's questions
    public $results_disabled;
    public $expired_time;
    public $course;
    public $course_id;
    public $propagate_neg;
    public $review_answers;
    public $randomByCat;
    public $text_when_finished;
    public $display_category_name;
    public $pass_percentage;
    public $edit_exercise_in_lp = false;
    public $is_gradebook_locked = false;
    public $exercise_was_added_in_lp = false;
    public $lpList = array();
    public $force_edit_exercise_in_lp = false;
    public $sessionId = 0;
    public $debug = false;

    /**
     * Constructor of the class
     *
     * @author Olivier Brouckaert
     */
    public function __construct($course_id = null)
    {
        $this->id = 0;
        $this->exercise = '';
        $this->description = '';
        $this->sound = '';
        $this->type = ALL_ON_ONE_PAGE;
        $this->random = 0;
        $this->random_answers = 0;
        $this->active = 1;
        $this->questionList = array();
        $this->timeLimit = 0;
        $this->end_time = '0000-00-00 00:00:00';
        $this->start_time = '0000-00-00 00:00:00';
        $this->results_disabled = 1;
        $this->expired_time = '0000-00-00 00:00:00';
        $this->propagate_neg = 0;
        $this->review_answers = false;
        $this->randomByCat = 0;
        $this->text_when_finished = '';
        $this->display_category_name = 0;
        $this->pass_percentage = '';

        if (!empty($course_id)) {
            $course_info = api_get_course_info_by_id($course_id);
        } else {
            $course_info = api_get_course_info();
        }
        $this->course_id = $course_info['real_id'];
        $this->course = $course_info;
        $this->sessionId = api_get_session_id();
    }

    /**
     * Reads exercise information from the data base
     *
     * @author Olivier Brouckaert
     * @param integer $id - exercise Id
     *
     * @return boolean - true if exercise exists, otherwise false
     */
    public function read($id)
    {
        $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_lp_item = Database::get_course_table(TABLE_LP_ITEM);

        $id  = intval($id);
        if (empty($this->course_id)) {
            return false;
        }
        $sql = "SELECT * FROM $TBL_EXERCISES WHERE c_id = ".$this->course_id." AND id = ".$id;
        $result = Database::query($sql);

        // if the exercise has been found
        if ($object = Database::fetch_object($result)) {
            $this->id = $id;
            $this->exercise = $object->title;
            $this->name = $object->title;
            $this->title = $object->title;
            $this->description = $object->description;
            $this->sound = $object->sound;
            $this->type = $object->type;
            if (empty($this->type)) {
                $this->type = ONE_PER_PAGE;
            }
            $this->random = $object->random;
            $this->random_answers = $object->random_answers;
            $this->active = $object->active;
            $this->results_disabled = $object->results_disabled;
            $this->attempts = $object->max_attempt;
            $this->feedback_type = $object->feedback_type;
            $this->propagate_neg = $object->propagate_neg;
            $this->randomByCat = $object->random_by_category;
            $this->text_when_finished = $object->text_when_finished;
            $this->display_category_name = $object->display_category_name;
            $this->pass_percentage = $object->pass_percentage;
            $this->sessionId = $object->session_id;

            $this->is_gradebook_locked = api_resource_is_locked_by_gradebook($id, LINK_EXERCISE);

            $this->review_answers = (isset($object->review_answers) && $object->review_answers == 1) ? true : false;

            $sql = "SELECT lp_id, max_score
                    FROM $table_lp_item
                    WHERE   c_id = {$this->course_id} AND
                            item_type = '".TOOL_QUIZ."' AND
                            path = '".$id."'";
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                $this->exercise_was_added_in_lp = true;
                $this->lpList = Database::store_result($result, 'ASSOC');
            }

            $this->force_edit_exercise_in_lp = api_get_configuration_value('force_edit_exercise_in_lp');

            if ($this->exercise_was_added_in_lp) {
                $this->edit_exercise_in_lp = $this->force_edit_exercise_in_lp == true;
            } else {
                $this->edit_exercise_in_lp = true;
            }

            if ($object->end_time != '0000-00-00 00:00:00') {
                $this->end_time 	= $object->end_time;
            }
            if ($object->start_time != '0000-00-00 00:00:00') {
                $this->start_time 	= $object->start_time;
            }

            //control time
            $this->expired_time 	= $object->expired_time;

            //Checking if question_order is correctly set
            $this->questionList     = $this->selectQuestionList(true);

            //overload questions list with recorded questions list
            //load questions only for exercises of type 'one question per page'
            //this is needed only is there is no questions
            /*
			// @todo not sure were in the code this is used somebody mess with the exercise tool
			// @todo don't know who add that config and why $_configuration['live_exercise_tracking']
			global $_configuration, $questionList;
			if ($this->type == ONE_PER_PAGE && $_SERVER['REQUEST_METHOD'] != 'POST' && defined('QUESTION_LIST_ALREADY_LOGGED') &&
			isset($_configuration['live_exercise_tracking']) && $_configuration['live_exercise_tracking']) {
				$this->questionList = $questionList;
			}*/
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getCutTitle()
    {
        return cut($this->exercise, EXERCISE_MAX_NAME_SIZE);
    }

    /**
     * returns the exercise ID
     *
     * @author Olivier Brouckaert
     * @return int - exercise ID
     */
    public function selectId()
    {
        return $this->id;
    }

    /**
     * returns the exercise title
     *
     * @author Olivier Brouckaert
     * @return string - exercise title
     */
    public function selectTitle()
    {
        return $this->exercise;
    }

    /**
     * returns the number of attempts setted
     *
     * @return int - exercise attempts
     */
    public function selectAttempts()
    {
        return $this->attempts;
    }

    /** returns the number of FeedbackType  *
     *  0=>Feedback , 1=>DirectFeedback, 2=>NoFeedback
     * @return int - exercise attempts
     */
    public function selectFeedbackType()
    {
        return $this->feedback_type;
    }

    /**
     * returns the time limit
     */
    public function selectTimeLimit()
    {
        return $this->timeLimit;
    }

    /**
     * returns the exercise description
     *
     * @author Olivier Brouckaert
     * @return string - exercise description
     */
    public function selectDescription()
    {
        return $this->description;
    }

    /**
     * returns the exercise sound file
     *
     * @author Olivier Brouckaert
     * @return string - exercise description
     */
    public function selectSound()
    {
        return $this->sound;
    }

    /**
     * returns the exercise type
     *
     * @author Olivier Brouckaert
     * @return integer - exercise type
     */
    public function selectType()
    {
        return $this->type;
    }

    /**
     * @author hubert borderiou 30-11-11
     * @return integer : do we display the question category name for students
     */
    public function selectDisplayCategoryName()
    {
        return $this->display_category_name;
    }

    /**
     * @return int
     */
    public function selectPassPercentage()
    {
        return $this->pass_percentage;
    }

    /**
     *
     * Modify object to update the switch display_category_name
     * @author hubert borderiou 30-11-11
     * @param int $in_txt is an integer 0 or 1
     */
    public function updateDisplayCategoryName($in_txt)
    {
        $this->display_category_name = $in_txt;
    }

    /**
     * @author hubert borderiou 28-11-11
     * @return string html text : the text to display ay the end of the test.
     */
    public function selectTextWhenFinished()
    {
        return $this->text_when_finished;
    }

    /**
     * @author hubert borderiou 28-11-11
     * @return string  html text : update the text to display ay the end of the test.
     */
    public function updateTextWhenFinished($in_txt)
    {
        $this->text_when_finished = $in_txt;
    }

    /**
     * return 1 or 2 if randomByCat
     * @author hubert borderiou
     * @return integer - quiz random by category
     */
    public function selectRandomByCat()
    {
        return $this->randomByCat;
    }

    /**
     * return 0 if no random by cat
     * return 1 if random by cat, categories shuffled
     * return 2 if random by cat, categories sorted by alphabetic order
     * @author hubert borderiou
     * @return integer - quiz random by category
     */
    public function isRandomByCat()
    {
        $res = 0;
        if ($this->randomByCat == 1) {
            $res = 1;
        } else if ($this->randomByCat == 2) {
            $res = 2;
        }
        return $res;
    }

    /**
     * return nothing
     * update randomByCat value for object
     * @author hubert borderiou
     */
    public function updateRandomByCat($in_randombycat)
    {
        if ($in_randombycat == 1) {
            $this->randomByCat = 1;
        } else if ($in_randombycat == 2) {
            $this->randomByCat = 2;
        } else {
            $this->randomByCat = 0;
        }
    }

    /**
     * Tells if questions are selected randomly, and if so returns the draws
     *
     * @author Carlos Vargas
     * @return integer - results disabled exercise
     */
    public function selectResultsDisabled()
    {
        return $this->results_disabled;
    }

    /**
     * tells if questions are selected randomly, and if so returns the draws
     *
     * @author Olivier Brouckaert
     * @return integer - 0 if not random, otherwise the draws
     */
    public function isRandom()
    {
        if($this->random > 0 || $this->random == -1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * returns random answers status.
     *
     * @author Juan Carlos Rana
     */
    public function selectRandomAnswers()
    {
        return $this->random_answers;
    }

    /**
     * Same as isRandom() but has a name applied to values different than 0 or 1
     */
    public function getShuffle()
    {
        return $this->random;
    }

    /**
     * returns the exercise status (1 = enabled ; 0 = disabled)
     *
     * @author Olivier Brouckaert
     * @return boolean - true if enabled, otherwise false
     */
    public function selectStatus()
    {
        return $this->active;
    }

    /**
     * returns the array with the question ID list
     *
     * @author Olivier Brouckaert
     * @return array - question ID list
     */
    public function selectQuestionList($from_db = false)
    {
        if ($from_db && !empty($this->id)) {
            $TBL_EXERCISE_QUESTION  = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

            $sql = "SELECT DISTINCT e.question_order
                    FROM $TBL_EXERCISE_QUESTION e
                    INNER JOIN $TBL_QUESTIONS  q
                    ON (e.question_id = q.id AND e.c_id = ".$this->course_id." AND q.c_id = ".$this->course_id.")
					WHERE e.exercice_id	= ".intval($this->id);
            $result = Database::query($sql);

            $count_question_orders = Database::num_rows($result);

            $sql = "SELECT DISTINCT e.question_id, e.question_order
                    FROM $TBL_EXERCISE_QUESTION e
                    INNER JOIN $TBL_QUESTIONS  q
                    ON (e.question_id= q.id AND e.c_id = ".$this->course_id." AND q.c_id = ".$this->course_id.")
					WHERE e.exercice_id	= ".intval($this->id)."
					ORDER BY question_order";
            $result = Database::query($sql);

            // fills the array with the question ID for this exercise
            // the key of the array is the question position
            $temp_question_list = array();
            $counter = 1;
            $question_list = array();

            while ($new_object = Database::fetch_object($result)) {
                $question_list[$new_object->question_order]=  $new_object->question_id;
                $temp_question_list[$counter] = $new_object->question_id;
                $counter++;
            }

            if (!empty($temp_question_list)) {
                if (count($temp_question_list) != $count_question_orders) {
                    $question_list = $temp_question_list;
                }
            }

            return $question_list;
        }

        return $this->questionList;
    }

    /**
     * returns the number of questions in this exercise
     *
     * @author Olivier Brouckaert
     * @return integer - number of questions
     */
    public function selectNbrQuestions()
    {
        return sizeof($this->questionList);
    }

    /**
     * @return int
     */
    public function selectPropagateNeg()
    {
        return $this->propagate_neg;
    }

    /**
     * Selects questions randomly in the question list
     *
     * @author Olivier Brouckaert
     * @author Hubert Borderiou 15 nov 2011
     * @return array - if the exercise is not set to take questions randomly, returns the question list
     *					 without randomizing, otherwise, returns the list with questions selected randomly
     */
    public function selectRandomList()
    {
        $nbQuestions	= $this->selectNbrQuestions();
        $temp_list		= $this->questionList;

        //Not a random exercise, or if there are not at least 2 questions
        if($this->random == 0 || $nbQuestions < 2) {
            return $this->questionList;
        }
        if ($nbQuestions != 0) {
            shuffle($temp_list);
            $my_random_list = array_combine(range(1,$nbQuestions),$temp_list);
            $my_question_list = array();
            // $this->random == -1 if random with all questions
            if ($this->random > 0) {
                $i = 0;
                foreach ($my_random_list as $item) {
                    if ($i < $this->random) {
                        $my_question_list[$i] = $item;
                    } else {
                        break;
                    }
                    $i++;
                }
            } else {
                $my_question_list = $my_random_list;
            }
            return $my_question_list;
        }
    }

    /**
     * returns 'true' if the question ID is in the question list
     *
     * @author Olivier Brouckaert
     * @param integer $questionId - question ID
     * @return boolean - true if in the list, otherwise false
     */
    public function isInList($questionId)
    {
        if (is_array($this->questionList))
            return in_array($questionId,$this->questionList);
        else
            return false;
    }

    /**
     * changes the exercise title
     *
     * @author Olivier Brouckaert
     * @param string $title - exercise title
     */
    public function updateTitle($title)
    {
        $this->exercise=$title;
    }

    /**
     * changes the exercise max attempts
     *
     * @param int $attempts - exercise max attempts
     */
    public function updateAttempts($attempts)
    {
        $this->attempts=$attempts;
    }

    /**
     * changes the exercise feedback type
     *
     * @param int $feedback_type
     */
    public function updateFeedbackType($feedback_type)
    {
        $this->feedback_type=$feedback_type;
    }

    /**
     * changes the exercise description
     *
     * @author Olivier Brouckaert
     * @param string $description - exercise description
     */
    public function updateDescription($description)
    {
        $this->description=$description;
    }

    /**
     * changes the exercise expired_time
     *
     * @author Isaac flores
     * @param int $expired_time The expired time of the quiz
     */
    public function updateExpiredTime($expired_time)
    {
        $this->expired_time = $expired_time;
    }

    /**
     * @param $value
     */
    public function updatePropagateNegative($value)
    {
        $this->propagate_neg = $value;
    }

    /**
     * @param $value
     */
    public function updateReviewAnswers($value)
    {
        $this->review_answers = isset($value) && $value ? true : false;
    }

    /**
     * @param $value
     */
    public function updatePassPercentage($value)
    {
        $this->pass_percentage = $value;
    }

    /**
     * changes the exercise sound file
     *
     * @author Olivier Brouckaert
     * @param string $sound - exercise sound file
     * @param string $delete - ask to delete the file
     */
    public function updateSound($sound,$delete)
    {
        global $audioPath, $documentPath;
        $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);

        if ($sound['size'] && (strstr($sound['type'],'audio') || strstr($sound['type'],'video'))) {
            $this->sound=$sound['name'];

            if (@move_uploaded_file($sound['tmp_name'],$audioPath.'/'.$this->sound)) {
                $query = "SELECT 1 FROM $TBL_DOCUMENT
                        WHERE c_id = ".$this->course_id." AND path='".str_replace($documentPath,'',$audioPath).'/'.$this->sound."'";
                $result=Database::query($query);

                if (!Database::num_rows($result)) {
                    $id = add_document(
                        $this->course,
                        str_replace($documentPath,'',$audioPath).'/'.$this->sound,
                        'file',
                        $sound['size'],
                        $sound['name']
                    );
                    api_item_property_update(
                        $this->course,
                        TOOL_DOCUMENT,
                        $id,
                        'DocumentAdded',
                        api_get_user_id()
                    );
                    item_property_update_on_folder(
                        $this->course,
                        str_replace($documentPath, '', $audioPath),
                        api_get_user_id()
                    );
                }
            }
        } elseif($delete && is_file($audioPath.'/'.$this->sound)) {
            $this->sound='';
        }
    }

    /**
     * changes the exercise type
     *
     * @author Olivier Brouckaert
     * @param integer $type - exercise type
     */
    public function updateType($type)
    {
        $this->type=$type;
    }

    /**
     * sets to 0 if questions are not selected randomly
     * if questions are selected randomly, sets the draws
     *
     * @author Olivier Brouckaert
     * @param integer $random - 0 if not random, otherwise the draws
     */
    public function setRandom($random)
    {
        /*if ($random == 'all') {
            $random = $this->selectNbrQuestions();
        }*/
        $this->random = $random;
    }

    /**
     * sets to 0 if answers are not selected randomly
     * if answers are selected randomly
     * @author Juan Carlos Rana
     * @param integer $random_answers - random answers
     */
    public function updateRandomAnswers($random_answers)
    {
        $this->random_answers = $random_answers;
    }

    /**
     * enables the exercise
     *
     * @author Olivier Brouckaert
     */
    public function enable()
    {
        $this->active=1;
    }

    /**
     * disables the exercise
     *
     * @author Olivier Brouckaert
     */
    public function disable()
    {
        $this->active=0;
    }

    /**
     * Set disable results
     */
    public function disable_results()
    {
        $this->results_disabled = true;
    }

    /**
     * Enable results
     */
    public function enable_results()
    {
        $this->results_disabled = false;
    }

    /**
     * @param int $results_disabled
     */
    public function updateResultsDisabled($results_disabled)
    {
        $this->results_disabled = intval($results_disabled);
    }

    /**
     * updates the exercise in the data base
     *
     * @author Olivier Brouckaert
     */
    public function save($type_e = '')
    {
        $_course = $this->course;
        $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);

        $id = $this->id;
        $exercise = $this->exercise;
        $description = $this->description;
        $sound = $this->sound;
        $type = $this->type;
        $attempts = isset($this->attempts) ? $this->attempts : 0;
        $feedback_type = isset($this->feedback_type) ? $this->feedback_type : 0;
        $random = $this->random;
        $random_answers = $this->random_answers;
        $active = $this->active;
        $propagate_neg = $this->propagate_neg;
        $review_answers = isset($this->review_answers) && $this->review_answers ? 1 : 0;
        $randomByCat = $this->randomByCat;
        $text_when_finished = $this->text_when_finished;
        $display_category_name = intval($this->display_category_name);
        $pass_percentage = intval($this->pass_percentage);
        $session_id = $this->sessionId;

        //If direct we do not show results
        if ($feedback_type == EXERCISE_FEEDBACK_TYPE_DIRECT) {
            $results_disabled = 0;
        } else {
            $results_disabled = intval($this->results_disabled);
        }

        $expired_time = intval($this->expired_time);

        // Exercise already exists
        if ($id) {
            // we prepare date in the database using the api_get_utc_datetime() function
            if (!empty($this->start_time) && $this->start_time != '0000-00-00 00:00:00') {
                $start_time = $this->start_time;
            } else {
                $start_time = '0000-00-00 00:00:00';
            }

            if (!empty($this->end_time) && $this->end_time != '0000-00-00 00:00:00') {
                $end_time = $this->end_time;
            } else {
                $end_time = '0000-00-00 00:00:00';
            }

            $params = [
                'title' => $exercise,
                'description' => $description,
            ];

            $paramsExtra = [];
            if ($type_e != 'simple') {
                $paramsExtra = [
                    'sound' => $sound,
                    'type' => $type,
                    'random' => $random,
                    'random_answers' => $random_answers,
                    'active' => $active,
                    'feedback_type' => $feedback_type,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'max_attempt' => $attempts,
                    'expired_time' => $expired_time,
                    'propagate_neg' => $propagate_neg,
                    'review_answers' => $review_answers,
                    'random_by_category' => $randomByCat,
                    'text_when_finished' => $text_when_finished,
                    'display_category_name' => $display_category_name,
                    'pass_percentage' => $pass_percentage,
                    'results_disabled' => $results_disabled,
                ];
            }

            $params = array_merge($params, $paramsExtra);

            Database::update(
                $TBL_EXERCISES,
                $params,
                ['c_id = ? AND id = ?' => [$this->course_id, $id]]
            );

            // update into the item_property table
            api_item_property_update(
                $_course,
                TOOL_QUIZ,
                $id,
                'QuizUpdated',
                api_get_user_id()
            );

            if (api_get_setting('search_enabled')=='true') {
                $this->search_engine_edit();
            }
        } else {
            // Creates a new exercise

            // In this case of new exercise, we don't do the api_get_utc_datetime()
            // for date because, bellow, we call function api_set_default_visibility()
            // In this function, api_set_default_visibility,
            // the Quiz is saved too, with an $id and api_get_utc_datetime() is done.
            // If we do it now, it will be done twice (cf. https://support.chamilo.org/issues/6586)
            if (!empty($this->start_time) && $this->start_time != '0000-00-00 00:00:00') {
                $start_time = $this->start_time;
            } else {
                $start_time = '0000-00-00 00:00:00';
            }

            if (!empty($this->end_time) && $this->end_time != '0000-00-00 00:00:00') {
                $end_time = $this->end_time;
            } else {
                $end_time = '0000-00-00 00:00:00';
            }

            $params = [
                'c_id' => $this->course_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'title' => $exercise,
                'description' => $description,
                'sound' => $sound,
                'type' => $type,
                'random' => $random,
                'random_answers' => $random_answers,
                'active' => $active,
                'results_disabled' => $results_disabled,
                'max_attempt' => $attempts,
                'feedback_type' => $feedback_type,
                'expired_time' => $expired_time,
                'session_id' => $session_id,
                'review_answers' => $review_answers,
                'random_by_category' => $randomByCat,
                'text_when_finished' => $text_when_finished,
                'display_category_name' => $display_category_name,
                'pass_percentage' => $pass_percentage
            ];

            $this->id = Database::insert($TBL_EXERCISES, $params);

            if ($this->id) {

                $sql = "UPDATE $TBL_EXERCISES SET id = iid WHERE iid = {$this->id} ";
                Database::query($sql);

                // insert into the item_property table
                api_item_property_update(
                    $this->course,
                    TOOL_QUIZ,
                    $this->id,
                    'QuizAdded',
                    api_get_user_id()
                );

                // This function save the quiz again, carefull about start_time
                // and end_time if you remove this line (see above)
                api_set_default_visibility(
                    $this->id,
                    TOOL_QUIZ,
                    null,
                    $this->course
                );

                if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
                    $this->search_engine_save();
                }
            }
        }

        // Updates the question position
        $this->update_question_positions();
    }

    /**
     * Updates question position
     */
    public function update_question_positions()
    {
        $quiz_question_table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        //Fixes #3483 when updating order
        $question_list = $this->selectQuestionList(true);
        if (!empty($question_list)) {
            foreach ($question_list as $position => $questionId) {
                $sql = "UPDATE $quiz_question_table SET
                        question_order ='".intval($position)."'
                        WHERE
                            c_id = ".$this->course_id." AND
                            question_id = ".intval($questionId)." AND
                            exercice_id=".intval($this->id);
                Database::query($sql);
            }
        }
    }

    /**
     * Adds a question into the question list
     *
     * @author Olivier Brouckaert
     * @param integer $questionId - question ID
     * @return boolean - true if the question has been added, otherwise false
     */
    public function addToList($questionId)
    {
        // checks if the question ID is not in the list
        if (!$this->isInList($questionId)) {
            // selects the max position
            if (!$this->selectNbrQuestions()) {
                $pos = 1;
            } else {
                if (is_array($this->questionList)) {
                    $pos = max(array_keys($this->questionList)) + 1;
                }
            }
            $this->questionList[$pos] = $questionId;

            return true;
        }

        return false;
    }

    /**
     * removes a question from the question list
     *
     * @author Olivier Brouckaert
     * @param integer $questionId - question ID
     * @return boolean - true if the question has been removed, otherwise false
     */
    public function removeFromList($questionId)
    {
        // searches the position of the question ID in the list
        $pos = array_search($questionId,$this->questionList);

        // question not found
        if ($pos === false) {
            return false;
        } else {
            // dont reduce the number of random question if we use random by category option, or if
            // random all questions
            if ($this->isRandom() && $this->isRandomByCat() == 0) {
                if (count($this->questionList) >= $this->random && $this->random > 0) {
                    $this->random -= 1;
                    $this->save();
                }
            }
            // deletes the position from the array containing the wanted question ID
            unset($this->questionList[$pos]);
            return true;
        }
    }

    /**
     * deletes the exercise from the database
     * Notice : leaves the question in the data base
     *
     * @author Olivier Brouckaert
     */
    public function delete()
    {
        $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "UPDATE $TBL_EXERCISES SET active='-1'
                WHERE c_id = ".$this->course_id." AND id = ".intval($this->id)."";
        Database::query($sql);
        api_item_property_update($this->course, TOOL_QUIZ, $this->id, 'QuizDeleted', api_get_user_id());
        api_item_property_update($this->course, TOOL_QUIZ, $this->id, 'delete', api_get_user_id());

        if (api_get_setting('search_enabled')=='true' && extension_loaded('xapian') ) {
            $this->search_engine_delete();
        }
    }

    /**
     * Creates the form to create / edit an exercise
     * @param FormValidator $form
     */
    public function createForm($form, $type='full')
    {
        if (empty($type)) {
            $type = 'full';
        }

        // form title
        if (!empty($_GET['exerciseId'])) {
            $form_title = get_lang('ModifyExercise');
        } else {
            $form_title = get_lang('NewEx');
        }

        $form->addElement('header', $form_title);

        // Title.
        $form->addElement(
            'text',
            'exerciseTitle',
            get_lang('ExerciseName'),
            array('id' => 'exercise_title')
        );

        $form->addElement('advanced_settings', 'advanced_params', get_lang('AdvancedParameters'));
        $form->addElement('html', '<div id="advanced_params_options" style="display:none">');

        $editor_config = array(
            'ToolbarSet' => 'TestQuestionDescription',
            'Width' => '100%',
            'Height' => '150',
        );
        if (is_array($type)){
            $editor_config = array_merge($editor_config, $type);
        }

        $form->addHtmlEditor(
            'exerciseDescription',
            get_lang('ExerciseDescription'),
            false,
            false,
            $editor_config
        );

        if ($type == 'full') {
            //Can't modify a DirectFeedback question
            if ($this->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_DIRECT) {
                // feedback type
                $radios_feedback = array();
                $radios_feedback[] = $form->createElement(
                    'radio',
                    'exerciseFeedbackType',
                    null,
                    get_lang('ExerciseAtTheEndOfTheTest'),
                    '0',
                    array(
                        'id' => 'exerciseType_0',
                        'onclick' => 'check_feedback()',
                    )
                );

                if (api_get_setting('enable_quiz_scenario') == 'true') {
                    //Can't convert a question from one feedback to another if there is more than 1 question already added
                    if ($this->selectNbrQuestions() == 0) {
                        $radios_feedback[] = $form->createElement(
                            'radio',
                            'exerciseFeedbackType',
                            null,
                            get_lang('DirectFeedback'),
                            '1',
                            array(
                                'id' => 'exerciseType_1',
                                'onclick' => 'check_direct_feedback()',
                            )
                        );
                    }
                }

                $radios_feedback[] = $form->createElement(
                    'radio',
                    'exerciseFeedbackType',
                    null,
                    get_lang('NoFeedback'),
                    '2',
                    array('id' => 'exerciseType_2')
                );
                $form->addGroup($radios_feedback, null, array(get_lang('FeedbackType'),get_lang('FeedbackDisplayOptions')), '');

                // Type of results display on the final page
                $radios_results_disabled = array();
                $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('ShowScoreAndRightAnswer'), '0', array('id'=>'result_disabled_0'));
                $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('DoNotShowScoreNorRightAnswer'),  '1',array('id'=>'result_disabled_1','onclick' => 'check_results_disabled()'));
                $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('OnlyShowScore'),  '2', array('id'=>'result_disabled_2'));
                //$radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('ExamModeWithFinalScoreShowOnlyFinalScoreWithCategoriesIfAvailable'),  '3', array('id'=>'result_disabled_3','onclick' => 'check_results_disabled()'));

                $form->addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents'), '');

                // Type of questions disposition on page
                $radios = array();

                $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'),    '1', array('onclick' => 'check_per_page_all()', 'id'=>'option_page_all'));
                $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'),'2', array('onclick' => 'check_per_page_one()', 'id'=>'option_page_one'));

                $form->addGroup($radios, null, get_lang('QuestionsPerPage'), '');

            } else {
                // if is Directfeedback but has not questions we can allow to modify the question type
                if ($this->selectNbrQuestions() == 0) {

                    // feedback type
                    $radios_feedback = array();
                    $radios_feedback[] = $form->createElement('radio', 'exerciseFeedbackType', null, get_lang('ExerciseAtTheEndOfTheTest'),'0',array('id' =>'exerciseType_0', 'onclick' => 'check_feedback()'));

                    if (api_get_setting('enable_quiz_scenario') == 'true') {
                        $radios_feedback[] = $form->createElement('radio', 'exerciseFeedbackType', null, get_lang('DirectFeedback'), '1', array('id' =>'exerciseType_1' , 'onclick' => 'check_direct_feedback()'));
                    }
                    $radios_feedback[] = $form->createElement('radio', 'exerciseFeedbackType', null, get_lang('NoFeedback'),'2',array('id' =>'exerciseType_2'));
                    $form->addGroup($radios_feedback, null, array(get_lang('FeedbackType'),get_lang('FeedbackDisplayOptions')));

                    //$form->addElement('select', 'exerciseFeedbackType',get_lang('FeedbackType'),$feedback_option,'onchange="javascript:feedbackselection()"');
                    $radios_results_disabled = array();
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('ShowScoreAndRightAnswer'), '0', array('id'=>'result_disabled_0'));
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('DoNotShowScoreNorRightAnswer'),  '1',array('id'=>'result_disabled_1','onclick' => 'check_results_disabled()'));
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('OnlyShowScore'),  '2',array('id'=>'result_disabled_2','onclick' => 'check_results_disabled()'));
                    $form->addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents'),'');

                    // Type of questions disposition on page
                    $radios = array();
                    $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'),    '1');
                    $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'),'2');
                    $form->addGroup($radios, null, get_lang('ExerciseType'));

                } else {
                    //Show options freeze
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('ShowScoreAndRightAnswer'), '0', array('id'=>'result_disabled_0'));
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('DoNotShowScoreNorRightAnswer'),  '1',array('id'=>'result_disabled_1','onclick' => 'check_results_disabled()'));
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('OnlyShowScore'),  '2',array('id'=>'result_disabled_2','onclick' => 'check_results_disabled()'));
                    $result_disable_group = $form->addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents'),'');
                    $result_disable_group->freeze();

                    //we force the options to the DirectFeedback exercisetype
                    $form->addElement('hidden', 'exerciseFeedbackType', EXERCISE_FEEDBACK_TYPE_DIRECT);
                    $form->addElement('hidden', 'exerciseType', ONE_PER_PAGE);

                    // Type of questions disposition on page
                    $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'),    '1', array('onclick' => 'check_per_page_all()', 'id'=>'option_page_all'));
                    $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'),'2', array('onclick' => 'check_per_page_one()', 'id'=>'option_page_one'));

                    $type_group = $form->addGroup($radios, null, get_lang('QuestionsPerPage'), '');
                    $type_group->freeze();
                }
            }

            // number of random question

            $max = ($this->id > 0) ? $this->selectNbrQuestions() : 10 ;
            $option = range(0,$max);
            $option[0] = get_lang('No');
            $option[-1] = get_lang('AllQuestionsShort');
            $form->addElement('select', 'randomQuestions',array(get_lang('RandomQuestions'), get_lang('RandomQuestionsHelp')), $option, array('id'=>'randomQuestions'));

            // Random answers
            $radios_random_answers = array();
            $radios_random_answers[] = $form->createElement('radio', 'randomAnswers', null, get_lang('Yes'),'1');
            $radios_random_answers[] = $form->createElement('radio', 'randomAnswers', null, get_lang('No'),'0');
            $form->addGroup($radios_random_answers, null, get_lang('RandomAnswers'), '');

            // Random by category
            $form->addElement('html','<div class="clear">&nbsp;</div>');
            $radiocat = array();
            $radiocat[] = $form->createElement('radio', 'randomByCat', null, get_lang('YesWithCategoriesShuffled'),'1');
            $radiocat[] = $form->createElement('radio', 'randomByCat', null, get_lang('YesWithCategoriesSorted'),'2');
            $radiocat[] = $form->createElement('radio', 'randomByCat', null, get_lang('No'),'0');
            $radioCatGroup = $form->addGroup($radiocat, null, get_lang('RandomQuestionByCategory'), '');
            $form->addElement('html','<div class="clear">&nbsp;</div>');

            // add the radio display the category name for student
            $radio_display_cat_name = array();
            $radio_display_cat_name[] = $form->createElement('radio', 'display_category_name', null, get_lang('Yes'), '1');
            $radio_display_cat_name[] = $form->createElement('radio', 'display_category_name', null, get_lang('No'), '0');
            $form->addGroup($radio_display_cat_name, null, get_lang('QuestionDisplayCategoryName'), '');

            // Attempts
            $attempt_option = range(0, 10);
            $attempt_option[0] = get_lang('Infinite');

            $form->addElement(
                'select',
                'exerciseAttempts',
                get_lang('ExerciseAttempts'),
                $attempt_option,
                ['id' => 'exerciseAttempts']
            );

            // Exercise time limit
            $form->addElement('checkbox', 'activate_start_date_check',null, get_lang('EnableStartTime'), array('onclick' => 'activate_start_date()'));

            $var = Exercise::selectTimeLimit();

            if (($this->start_time != '0000-00-00 00:00:00'))
                $form->addElement('html','<div id="start_date_div" style="display:block;">');
            else
                $form->addElement('html','<div id="start_date_div" style="display:none;">');

            $form->addElement('date_time_picker', 'start_time');

            $form->addElement('html','</div>');

            $form->addElement('checkbox', 'activate_end_date_check', null , get_lang('EnableEndTime'), array('onclick' => 'activate_end_date()'));

            if (($this->end_time != '0000-00-00 00:00:00'))
                $form->addElement('html','<div id="end_date_div" style="display:block;">');
            else
                $form->addElement('html','<div id="end_date_div" style="display:none;">');

            $form->addElement('date_time_picker', 'end_time');
            $form->addElement('html','</div>');

            //$check_option=$this->selectType();
            $diplay = 'block';
            $form->addElement('checkbox', 'propagate_neg', null, get_lang('PropagateNegativeResults'));
            $form->addElement('html','<div class="clear">&nbsp;</div>');
            $form->addElement('checkbox', 'review_answers', null, get_lang('ReviewAnswers'));

            $form->addElement('html','<div id="divtimecontrol"  style="display:'.$diplay.';">');

            //Timer control
            //$time_hours_option = range(0,12);
            //$time_minutes_option = range(0,59);
            $form->addElement(
                'checkbox',
                'enabletimercontrol',
                null,
                get_lang('EnableTimerControl'),
                array(
                    'onclick' => 'option_time_expired()',
                    'id' => 'enabletimercontrol',
                    'onload' => 'check_load_time()',
                )
            );
            $expired_date = (int)$this->selectExpiredTime();

            if (($expired_date!='0')) {
                $form->addElement('html','<div id="timercontrol" style="display:block;">');
            } else {
                $form->addElement('html','<div id="timercontrol" style="display:none;">');
            }
            $form->addText(
                'enabletimercontroltotalminutes',
                get_lang('ExerciseTotalDurationInMinutes'),
                false,
                [
                    'id' => 'enabletimercontroltotalminutes',
                    'cols-size' => [2, 2, 8]
                ]
            );
            $form->addElement('html','</div>');

            $form->addElement(
                'text',
                'pass_percentage',
                array(get_lang('PassPercentage'), null, '%'),
                array('id' => 'pass_percentage')
            );
            $form->addRule('pass_percentage', get_lang('Numeric'), 'numeric');

            // add the text_when_finished textbox
            $form->addHtmlEditor(
                'text_when_finished',
                get_lang('TextWhenFinished'),
                false,
                false,
                $editor_config
            );

            $defaults = array();

            if (api_get_setting('search_enabled') === 'true') {
                require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

                $form->addElement ('checkbox', 'index_document','', get_lang('SearchFeatureDoIndexDocument'));
                $form->addElement ('select_language', 'language', get_lang('SearchFeatureDocumentLanguage'));

                $specific_fields = get_specific_field_list();

                foreach ($specific_fields as $specific_field) {
                    $form->addElement ('text', $specific_field['code'], $specific_field['name']);
                    $filter = array('c_id'=> "'". api_get_course_int_id() ."'", 'field_id' => $specific_field['id'], 'ref_id' => $this->id, 'tool_id' => '\''. TOOL_QUIZ .'\'');
                    $values = get_specific_field_values_list($filter, array('value'));
                    if ( !empty($values) ) {
                        $arr_str_values = array();
                        foreach ($values as $value) {
                            $arr_str_values[] = $value['value'];
                        }
                        $defaults[$specific_field['code']] = implode(', ', $arr_str_values);
                    }
                }
                //$form->addElement ('html','</div>');
            }

            $form->addElement('html','</div>');  //End advanced setting
            $form->addElement('html','</div>');
        }

        // submit
        if (isset($_GET['exerciseId'])) {
            $form->addButtonSave(get_lang('ModifyExercise'), 'submitExercise');
        } else {
            $form->addButtonUpdate(get_lang('ProcedToQuestions'), 'submitExercise');
        }

        $form->addRule('exerciseTitle', get_lang('GiveExerciseName'), 'required');

        if ($type == 'full') {
            // rules
            $form->addRule('exerciseAttempts', get_lang('Numeric'), 'numeric');
            $form->addRule('start_time', get_lang('InvalidDate'), 'datetime');
            $form->addRule('end_time', get_lang('InvalidDate'), 'datetime');
        }

        // defaults
        if ($type=='full') {
            if ($this->id > 0) {
                if ($this->random > $this->selectNbrQuestions()) {
                    $defaults['randomQuestions'] =  $this->selectNbrQuestions();
                } else {
                    $defaults['randomQuestions'] = $this->random;
                }

                $defaults['randomAnswers'] = $this->selectRandomAnswers();
                $defaults['exerciseType'] = $this->selectType();
                $defaults['exerciseTitle'] = $this->get_formated_title();
                $defaults['exerciseDescription'] = $this->selectDescription();
                $defaults['exerciseAttempts'] = $this->selectAttempts();
                $defaults['exerciseFeedbackType'] = $this->selectFeedbackType();
                $defaults['results_disabled'] = $this->selectResultsDisabled();
                $defaults['propagate_neg'] = $this->selectPropagateNeg();
                $defaults['review_answers'] = $this->review_answers;
                $defaults['randomByCat'] = $this->selectRandomByCat();
                $defaults['text_when_finished'] = $this->selectTextWhenFinished();
                $defaults['display_category_name'] = $this->selectDisplayCategoryName();
                $defaults['pass_percentage'] = $this->selectPassPercentage();

                if (($this->start_time != '0000-00-00 00:00:00')) {
                    $defaults['activate_start_date_check'] = 1;
                }
                if ($this->end_time != '0000-00-00 00:00:00') {
                    $defaults['activate_end_date_check'] = 1;
                }

                $defaults['start_time'] = ($this->start_time!='0000-00-00 00:00:00') ? api_get_local_time($this->start_time) : date('Y-m-d 12:00:00');
                $defaults['end_time'] = ($this->end_time!='0000-00-00 00:00:00') ? api_get_local_time($this->end_time) : date('Y-m-d 12:00:00', time()+84600);

                // Get expired time
                if ($this->expired_time != '0') {
                    $defaults['enabletimercontrol'] = 1;
                    $defaults['enabletimercontroltotalminutes'] = $this->expired_time;
                } else {
                    $defaults['enabletimercontroltotalminutes'] = 0;
                }
            } else {
                $defaults['exerciseType'] = 2;
                $defaults['exerciseAttempts'] = 0;
                $defaults['randomQuestions'] = 0;
                $defaults['randomAnswers'] = 0;
                $defaults['exerciseDescription'] = '';
                $defaults['exerciseFeedbackType'] = 0;
                $defaults['results_disabled'] = 0;
                $defaults['randomByCat'] = 0;
                $defaults['text_when_finished'] = "";
                $defaults['start_time'] = date('Y-m-d 12:00:00');
                $defaults['display_category_name'] = 1;
                $defaults['end_time']   = date('Y-m-d 12:00:00', time()+84600);
                $defaults['pass_percentage'] = '';
            }
        } else {
            $defaults['exerciseTitle'] = $this->selectTitle();
            $defaults['exerciseDescription'] = $this->selectDescription();
        }
        if (api_get_setting('search_enabled') === 'true') {
            $defaults['index_document'] = 'checked="checked"';
        }
        $form->setDefaults($defaults);

        // Freeze some elements.
        if ($this->id != 0 && $this->edit_exercise_in_lp == false) {
            $elementsToFreeze = array(
                'randomQuestions',
                //'randomByCat',
                'exerciseAttempts',
                'propagate_neg',
                'enabletimercontrol',
                'review_answers'
            );

            foreach ($elementsToFreeze as $elementName) {
                /** @var HTML_QuickForm_element $element */
                $element = $form->getElement($elementName);
                $element->freeze();
            }

            $radioCatGroup->freeze();
        }
    }

    /**
     * function which process the creation of exercises
     * @param FormValidator $form
     * @param string
     */
    function processCreation($form, $type = '')
    {
        $this->updateTitle(Exercise::format_title_variable($form->getSubmitValue('exerciseTitle')));
        $this->updateDescription($form->getSubmitValue('exerciseDescription'));
        $this->updateAttempts($form->getSubmitValue('exerciseAttempts'));
        $this->updateFeedbackType($form->getSubmitValue('exerciseFeedbackType'));
        $this->updateType($form->getSubmitValue('exerciseType'));
        $this->setRandom($form->getSubmitValue('randomQuestions'));
        $this->updateRandomAnswers($form->getSubmitValue('randomAnswers'));
        $this->updateResultsDisabled($form->getSubmitValue('results_disabled'));
        $this->updateExpiredTime($form->getSubmitValue('enabletimercontroltotalminutes'));
        $this->updatePropagateNegative($form->getSubmitValue('propagate_neg'));
        $this->updateRandomByCat($form->getSubmitValue('randomByCat'));
        $this->updateTextWhenFinished($form->getSubmitValue('text_when_finished'));
        $this->updateDisplayCategoryName($form->getSubmitValue('display_category_name'));
        $this->updateReviewAnswers($form->getSubmitValue('review_answers'));
        $this->updatePassPercentage($form->getSubmitValue('pass_percentage'));

        if ($form->getSubmitValue('activate_start_date_check') == 1) {
            $start_time = $form->getSubmitValue('start_time');
            $this->start_time = api_get_utc_datetime($start_time);
        } else {
            $this->start_time = '0000-00-00 00:00:00';
        }

        if ($form->getSubmitValue('activate_end_date_check') == 1) {
            $end_time = $form->getSubmitValue('end_time');
            $this->end_time = api_get_utc_datetime($end_time);
        } else {
            $this->end_time   = '0000-00-00 00:00:00';
        }

        if ($form->getSubmitValue('enabletimercontrol') == 1) {
            $expired_total_time = $form->getSubmitValue('enabletimercontroltotalminutes');
            if ($this->expired_time == 0) {
                $this->expired_time = $expired_total_time;
            }
        } else {
            $this->expired_time = 0;
        }

        if ($form->getSubmitValue('randomAnswers') == 1) {
            $this->random_answers=1;
        } else {
            $this->random_answers=0;
        }
        $this->save($type);
    }

    function search_engine_save()
    {
        if ($_POST['index_document'] != 1) {
            return;
        }
        $course_id = api_get_course_id();

        require_once api_get_path(LIBRARY_PATH) . 'search/ChamiloIndexer.class.php';
        require_once api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php';
        require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

        $specific_fields = get_specific_field_list();
        $ic_slide = new IndexableChunk();

        $all_specific_terms = '';
        foreach ($specific_fields as $specific_field) {
            if (isset($_REQUEST[$specific_field['code']])) {
                $sterms = trim($_REQUEST[$specific_field['code']]);
                if (!empty($sterms)) {
                    $all_specific_terms .= ' '. $sterms;
                    $sterms = explode(',', $sterms);
                    foreach ($sterms as $sterm) {
                        $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                        add_specific_field_value($specific_field['id'], $course_id, TOOL_QUIZ, $this->id, $sterm);
                    }
                }
            }
        }

        // build the chunk to index
        $ic_slide->addValue("title", $this->exercise);
        $ic_slide->addCourseId($course_id);
        $ic_slide->addToolId(TOOL_QUIZ);
        $xapian_data = array(
            SE_COURSE_ID => $course_id,
            SE_TOOL_ID => TOOL_QUIZ,
            SE_DATA => array('type' => SE_DOCTYPE_EXERCISE_EXERCISE, 'exercise_id' => (int)$this->id),
            SE_USER => (int)api_get_user_id(),
        );
        $ic_slide->xapian_data = serialize($xapian_data);
        $exercise_description = $all_specific_terms .' '. $this->description;
        $ic_slide->addValue("content", $exercise_description);

        $di = new ChamiloIndexer();
        isset($_POST['language'])? $lang=Database::escape_string($_POST['language']): $lang = 'english';
        $di->connectDb(NULL, NULL, $lang);
        $di->addChunk($ic_slide);

        //index and return search engine document id
        $did = $di->index();
        if ($did) {
            // save it to db
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
			    VALUES (NULL , \'%s\', \'%s\', %s, %s)';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id, $did);
            Database::query($sql);
        }
    }

    function search_engine_edit()
    {
        // update search enchine and its values table if enabled
        if (api_get_setting('search_enabled')=='true' && extension_loaded('xapian')) {
            $course_id = api_get_course_id();

            // actually, it consists on delete terms from db,
            // insert new ones, create a new search engine document, and remove the old one
            // get search_did
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            $res = Database::query($sql);

            if (Database::num_rows($res) > 0) {
                require_once(api_get_path(LIBRARY_PATH) . 'search/ChamiloIndexer.class.php');
                require_once(api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php');
                require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

                $se_ref = Database::fetch_array($res);
                $specific_fields = get_specific_field_list();
                $ic_slide = new IndexableChunk();

                $all_specific_terms = '';
                foreach ($specific_fields as $specific_field) {
                    delete_all_specific_field_value($course_id, $specific_field['id'], TOOL_QUIZ, $this->id);
                    if (isset($_REQUEST[$specific_field['code']])) {
                        $sterms = trim($_REQUEST[$specific_field['code']]);
                        $all_specific_terms .= ' '. $sterms;
                        $sterms = explode(',', $sterms);
                        foreach ($sterms as $sterm) {
                            $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                            add_specific_field_value($specific_field['id'], $course_id, TOOL_QUIZ, $this->id, $sterm);
                        }
                    }
                }

                // build the chunk to index
                $ic_slide->addValue("title", $this->exercise);
                $ic_slide->addCourseId($course_id);
                $ic_slide->addToolId(TOOL_QUIZ);
                $xapian_data = array(
                    SE_COURSE_ID => $course_id,
                    SE_TOOL_ID => TOOL_QUIZ,
                    SE_DATA => array('type' => SE_DOCTYPE_EXERCISE_EXERCISE, 'exercise_id' => (int)$this->id),
                    SE_USER => (int)api_get_user_id(),
                );
                $ic_slide->xapian_data = serialize($xapian_data);
                $exercise_description = $all_specific_terms .' '. $this->description;
                $ic_slide->addValue("content", $exercise_description);

                $di = new ChamiloIndexer();
                isset($_POST['language'])? $lang=Database::escape_string($_POST['language']): $lang = 'english';
                $di->connectDb(NULL, NULL, $lang);
                $di->remove_document((int)$se_ref['search_did']);
                $di->addChunk($ic_slide);

                //index and return search engine document id
                $did = $di->index();
                if ($did) {
                    // save it to db
                    $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=\'%s\'';
                    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
                    Database::query($sql);
                    $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
                        VALUES (NULL , \'%s\', \'%s\', %s, %s)';
                    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id, $did);
                    Database::query($sql);
                }
            } else {
                $this->search_engine_save();
            }
        }

    }

    function search_engine_delete()
    {
        // remove from search engine if enabled
        if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian') ) {
            $course_id = api_get_course_id();
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                require_once(api_get_path(LIBRARY_PATH) .'search/ChamiloIndexer.class.php');
                $di = new ChamiloIndexer();
                $di->remove_document((int)$row['search_did']);
                unset($di);
                $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
                foreach ( $this->questionList as $question_i) {
                    $sql = 'SELECT type FROM %s WHERE id=%s';
                    $sql = sprintf($sql, $tbl_quiz_question, $question_i);
                    $qres = Database::query($sql);
                    if (Database::num_rows($qres) > 0) {
                        $qrow = Database::fetch_array($qres);
                        $objQuestion = Question::getInstance($qrow['type']);
                        $objQuestion = Question::read((int)$question_i);
                        $objQuestion->search_engine_edit($this->id, FALSE, TRUE);
                        unset($objQuestion);
                    }
                }
            }
            $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            Database::query($sql);

            // remove terms from db
            require_once api_get_path(LIBRARY_PATH) .'specific_fields_manager.lib.php';
            delete_all_values_for_item($course_id, TOOL_QUIZ, $this->id);
        }
    }

    function selectExpiredTime()
    {
        return $this->expired_time;
    }

    /**
     * Cleans the student's results only for the Exercise tool (Not from the LP)
     * The LP results are NOT deleted by default, otherwise put $cleanLpTests = true
     * Works with exercises in sessions
     * @param bool $cleanLpTests
     * @param string $cleanResultBeforeDate
     *
     * @return int quantity of user's exercises deleted
     */
    public function clean_results($cleanLpTests = false, $cleanResultBeforeDate = null)
    {
        $table_track_e_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_e_attempt   = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql_where = '  AND
                        orig_lp_id = 0 AND
                        orig_lp_item_id = 0';

        // if we want to delete results from LP too
        if ($cleanLpTests) {
            $sql_where = "";
        }

        // if we want to delete attempts before date $cleanResultBeforeDate
        // $cleanResultBeforeDate must be a valid UTC-0 date yyyy-mm-dd

        if (!empty($cleanResultBeforeDate)) {
            $cleanResultBeforeDate = Database::escape_string($cleanResultBeforeDate);
            if (api_is_valid_date($cleanResultBeforeDate)) {
                $sql_where .= "  AND exe_date <= '$cleanResultBeforeDate' ";
            } else {
                return 0;
            }
        }

        $sql = "SELECT exe_id
                FROM $table_track_e_exercises
                WHERE
                    c_id = ".api_get_course_int_id()." AND
                    exe_exo_id = ".$this->id." AND
                    session_id = ".api_get_session_id()." ".
                    $sql_where;

        $result   = Database::query($sql);
        $exe_list = Database::store_result($result);

        // deleting TRACK_E_ATTEMPT table
        // check if exe in learning path or not
        $i = 0;
        if (is_array($exe_list) && count($exe_list) > 0) {
            foreach ($exe_list as $item) {
                $sql = "DELETE FROM $table_track_e_attempt
                        WHERE exe_id = '".$item['exe_id']."'";
                Database::query($sql);
                $i++;
            }
        }

        $session_id = api_get_session_id();
        // delete TRACK_E_EXERCISES table
        $sql = "DELETE FROM $table_track_e_exercises
                WHERE c_id = ".api_get_course_int_id()."
                AND exe_exo_id = ".$this->id."
                $sql_where
                AND session_id = ".$session_id."";
        Database::query($sql);

        Event::addEvent(
            LOG_EXERCISE_RESULT_DELETE,
            LOG_EXERCISE_ID,
            $this->id,
            null,
            null,
            api_get_course_int_id(),
            $session_id
        );

        return $i;
    }

    /**
     * Copies an exercise (duplicate all questions and answers)
     */
    public function copy_exercise()
    {
        $exercise_obj= new Exercise();
        $exercise_obj = $this;

        // force the creation of a new exercise
        $exercise_obj->updateTitle($exercise_obj->selectTitle().' - '.get_lang('Copy'));
        //Hides the new exercise
        $exercise_obj->updateStatus(false);
        $exercise_obj->updateId(0);
        $exercise_obj->save();

        $new_exercise_id = $exercise_obj->selectId();
        $question_list 	 = $exercise_obj->selectQuestionList();

        if (!empty($question_list)) {
            //Question creation

            foreach ($question_list as $old_question_id) {
                $old_question_obj = Question::read($old_question_id);
                $new_id = $old_question_obj->duplicate();
                if ($new_id) {
                    $new_question_obj = Question::read($new_id);

                    if (isset($new_question_obj) && $new_question_obj) {
                        $new_question_obj->addToList($new_exercise_id);
                        // This should be moved to the duplicate function
                        $new_answer_obj = new Answer($old_question_id);
                        $new_answer_obj->read();
                        $new_answer_obj->duplicate($new_id);
                    }
                }
            }
        }
    }

    /**
     * Changes the exercise id
     *
     * @param int $id - exercise id
     */
    private function updateId($id)
    {
        $this->id = $id;
    }

    /**
     * Changes the exercise status
     *
     * @param string $status - exercise status
     */
    function updateStatus($status)
    {
        $this->active = $status;
    }

    /**
     * @param int $lp_id
     * @param int $lp_item_id
     * @param int $lp_item_view_id
     * @param string $status
     * @return array
     */
    public function get_stat_track_exercise_info(
        $lp_id = 0,
        $lp_item_id = 0,
        $lp_item_view_id = 0,
        $status = 'incomplete'
    ) {
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        if (empty($lp_id)) {
            $lp_id = 0;
        }
        if (empty($lp_item_id)) {
            $lp_item_id   = 0;
        }
        if (empty($lp_item_view_id)) {
            $lp_item_view_id = 0;
        }
        $condition = ' WHERE exe_exo_id 	= ' . "'" . $this->id . "'" .' AND
					   exe_user_id 			= ' . "'" . api_get_user_id() . "'" . ' AND
					   c_id                 = ' . api_get_course_int_id() . ' AND
					   status 				= ' . "'" . Database::escape_string($status). "'" . ' AND
					   orig_lp_id 			= ' . "'" . $lp_id . "'" . ' AND
					   orig_lp_item_id 		= ' . "'" . $lp_item_id . "'" . ' AND
                       orig_lp_item_view_id = ' . "'" . $lp_item_view_id . "'" . ' AND
					   session_id 			= ' . "'" . api_get_session_id() . "' LIMIT 1"; //Adding limit 1 just in case

        $sql_track = 'SELECT * FROM '.$track_exercises.$condition;

        $result = Database::query($sql_track);
        $new_array = array();
        if (Database::num_rows($result) > 0 ) {
            $new_array = Database::fetch_array($result, 'ASSOC');
            $new_array['num_exe'] = Database::num_rows($result);
        }
        return $new_array;
    }

    /**
     * Saves a test attempt
     *
     * @param int  clock_expired_time
     * @param int  int lp id
     * @param int  int lp item id
     * @param int  int lp item_view id
     * @param float $weight
     * @param array question list
     */
    public function save_stat_track_exercise_info(
        $clock_expired_time = 0,
        $safe_lp_id = 0,
        $safe_lp_item_id = 0,
        $safe_lp_item_view_id = 0,
        $questionList = array(),
        $weight = 0
    ) {
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $safe_lp_id = intval($safe_lp_id);
        $safe_lp_item_id = intval($safe_lp_item_id);
        $safe_lp_item_view_id = intval($safe_lp_item_view_id);

        if (empty($safe_lp_id)) {
            $safe_lp_id = 0;
        }
        if (empty($safe_lp_item_id)) {
            $safe_lp_item_id = 0;
        }
        if (empty($clock_expired_time)) {
            $clock_expired_time = 0;
        }

        $questionList = array_map('intval', $questionList);

        $params = array(
            'exe_exo_id' => $this->id ,
            'exe_user_id' => api_get_user_id(),
            'c_id' => api_get_course_int_id(),
            'status' =>  'incomplete',
            'session_id'  => api_get_session_id(),
            'data_tracking'  => implode(',', $questionList) ,
            'start_date' => api_get_utc_datetime(),
            'orig_lp_id' => $safe_lp_id,
            'orig_lp_item_id'  => $safe_lp_item_id,
            'orig_lp_item_view_id'  => $safe_lp_item_view_id,
            'exe_weighting'=> $weight,
            'user_ip' => api_get_real_ip()
        );

        if ($this->expired_time != 0) {
            $params['expired_time_control'] = $clock_expired_time;
        }

        $id = Database::insert($track_exercises, $params);

        return $id;
    }

    /**
     * @param int $question_id
     * @param int $questionNum
     * @param array $questions_in_media
     * @param string $currentAnswer
     * @return string
     */
    public function show_button($question_id, $questionNum, $questions_in_media = array(), $currentAnswer = '')
    {
        global $origin, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id;

        $nbrQuestions = $this->get_count_question_list();

        $all_button = $html = $label = '';
        $hotspot_get = isset($_POST['hotspot']) ? Security::remove_XSS($_POST['hotspot']):null;

        if ($this->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT && $this->type == ONE_PER_PAGE) {
            $urlTitle = get_lang('ContinueTest');

            if ($questionNum == count($this->questionList)) {
                $urlTitle = get_lang('EndTest');
            }

            $html .= Display::url(
                $urlTitle,
                'exercise_submit_modal.php?' . http_build_query([
                    'learnpath_id' => $safe_lp_id,
                    'learnpath_item_id' => $safe_lp_item_id,
                    'learnpath_item_view_id' => $safe_lp_item_view_id,
                    'origin' => $origin,
                    'hotspot' => $hotspot_get,
                    'nbrQuestions' => $nbrQuestions,
                    'num' => $questionNum,
                    'exerciseType' => $this->type,
                    'exerciseId' => $this->id
                ]),
                [
                    'class' => 'ajax btn btn-default',
                    'data-title' => $urlTitle,
                    'data-size' => 'md'
                ]
            );
            $html .='<br />';
        } else {
            // User
            if (api_is_allowed_to_session_edit()) {
                if ($this->type == ALL_ON_ONE_PAGE || $nbrQuestions == $questionNum) {
                    if ($this->review_answers) {
                        $label = get_lang('ReviewQuestions');
                        $class = 'btn btn-success';
                    } else {
                        $label = get_lang('EndTest');
                        $class = 'btn btn-warning';
                    }
                } else {
                    $label = get_lang('NextQuestion');
                    $class = 'btn btn-primary';
                }
				$class .= ' question-validate-btn'; // used to select it with jquery
                if ($this->type == ONE_PER_PAGE) {
                    if ($questionNum != 1) {
                        $prev_question = $questionNum - 2;
                        $all_button .= '<a href="javascript://" class="btn btn-default" onclick="previous_question_and_save('.$prev_question.', '.$question_id.' ); ">'.get_lang('PreviousQuestion').'</a>';
                    }

                    //Next question
                    if (!empty($questions_in_media)) {
                        $questions_in_media = "['".implode("','",$questions_in_media)."']";
                        $all_button .= '&nbsp;<a href="javascript://" class="'.$class.'" onclick="save_question_list('.$questions_in_media.'); ">'.$label.'</a>';
                    } else {
                        $all_button .= '&nbsp;<a href="javascript://" class="'.$class.'" onclick="save_now('.$question_id.', \'\', \''.$currentAnswer.'\'); ">'.$label.'</a>';
                    }
                    $all_button .= '<span id="save_for_now_'.$question_id.'" class="exercise_save_mini_message"></span>&nbsp;';

                    $html .= $all_button;
                } else {
                    if ($this->review_answers) {
                        $all_label = get_lang('ReviewQuestions');
                        $class = 'btn btn-success';
                    } else {
                        $all_label = get_lang('EndTest');
                        $class = 'btn btn-warning';
                    }
					$class .= ' question-validate-btn'; // used to select it with jquery
                    $all_button = '&nbsp;<a href="javascript://" class="'.$class.'" onclick="validate_all(); ">'.$all_label.'</a>';
                    $all_button .= '&nbsp;' . Display::span(null, ['id' => 'save_all_reponse']);
                    $html .= $all_button;
                }
            }
        }
        return $html;
    }

    /**
     * So the time control will work
     *
     * @param string $time_left
     * @return string
     */
    public function show_time_control_js($time_left)
    {
        $time_left = intval($time_left);
        return "<script>

            function get_expired_date_string(expired_time) {
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                var day, month, year, hours, minutes, seconds, date_string;
                var obj_date = new Date(expired_time);
                day     = obj_date.getDate();
                if (day < 10) day = '0' + day;
                    month   = obj_date.getMonth();
                    year    = obj_date.getFullYear();
                    hours   = obj_date.getHours();
                if (hours < 10) hours = '0' + hours;
                minutes = obj_date.getMinutes();
                if (minutes < 10) minutes = '0' + minutes;
                seconds = obj_date.getSeconds();
                if (seconds < 10) seconds = '0' + seconds;
                date_string = months[month] +' ' + day + ', ' + year + ' ' + hours + ':' + minutes + ':' + seconds;
                return date_string;
            }

            function open_clock_warning() {
                $('#clock_warning').dialog({
                    modal:true,
                    height:250,
                    closeOnEscape: false,
                    resizable: false,
                    buttons: {
                        '".addslashes(get_lang("EndTest"))."': function() {
                            $('#clock_warning').dialog('close');
                        }
                    },
                    close: function() {
                        send_form();
                    }
                });
                $('#clock_warning').dialog('open');

                $('#counter_to_redirect').epiclock({
                    mode: $.epiclock.modes.countdown,
                    offset: {seconds: 5},
                    format: 's'
                }).bind('timer', function () {
                    send_form();
                });

            }

            function send_form() {
                if ($('#exercise_form').length) {
                    $('#exercise_form').submit();
                } else {
                    //In reminder
                    final_submit();
                }
            }

            function onExpiredTimeExercise() {
                $('#wrapper-clock').hide();
                $('#exercise_form').hide();
                $('#expired-message-id').show();

                //Fixes bug #5263
                $('#num_current_id').attr('value', '".$this->selectNbrQuestions()."');
                open_clock_warning();
            }

			$(document).ready(function() {

				var current_time = new Date().getTime();
                var time_left    = parseInt(".$time_left."); // time in seconds when using minutes there are some seconds lost
				var expired_time = current_time + (time_left*1000);
				var expired_date = get_expired_date_string(expired_time);

                $('#exercise_clock_warning').epiclock({
                    mode: $.epiclock.modes.countdown,
                    offset: {seconds: time_left},
                    format: 'x:i:s',
                    renderer: 'minute'
                }).bind('timer', function () {
                    onExpiredTimeExercise();
                });
	       		$('#submit_save').click(function () {});
	    });
	    </script>";
    }

    /**
     * Lp javascript for hotspots
     */
    public function show_lp_javascript()
    {
        return "";
    }

    /**
     * This function was originally found in the exercise_show.php
     * @param int       $exeId
     * @param int       $questionId
     * @param int       $choice the user selected
     * @param string    $from  function is called from 'exercise_show' or 'exercise_result'
     * @param array     $exerciseResultCoordinates the hotspot coordinates $hotspot[$question_id] = coordinates
     * @param bool      $saved_results save results in the DB or just show the reponse
     * @param bool      $from_database gets information from DB or from the current selection
     * @param bool      $show_result show results or not
     * @param int       $propagate_neg
     * @param array     $hotspot_delineation_result
     *
     * @todo    reduce parameters of this function
     * @return  string  html code
     */
    public function manage_answer(
        $exeId,
        $questionId,
        $choice,
        $from = 'exercise_show',
        $exerciseResultCoordinates = array(),
        $saved_results = true,
        $from_database = false,
        $show_result = true,
        $propagate_neg = 0,
        $hotspot_delineation_result = array()
    ) {
        global $debug;
        //needed in order to use in the exercise_attempt() for the time
        global $learnpath_id, $learnpath_item_id;
        require_once api_get_path(LIBRARY_PATH).'geometry.lib.php';

        $feedback_type = $this->selectFeedbackType();
        $results_disabled = $this->selectResultsDisabled();

        if ($debug) {
            error_log("<------ manage_answer ------> ");
            error_log('exe_id: '.$exeId);
            error_log('$from:  '.$from);
            error_log('$saved_results: '.intval($saved_results));
            error_log('$from_database: '.intval($from_database));
            error_log('$show_result: '.$show_result);
            error_log('$propagate_neg: '.$propagate_neg);
            error_log('$exerciseResultCoordinates: '.print_r($exerciseResultCoordinates, 1));
            error_log('$hotspot_delineation_result: '.print_r($hotspot_delineation_result, 1));
            error_log('$learnpath_id: '.$learnpath_id);
            error_log('$learnpath_item_id: '.$learnpath_item_id);
            error_log('$choice: '.print_r($choice, 1));
        }

        $extra_data = array();
        $final_overlap = 0;
        $final_missing = 0;
        $final_excess = 0;
        $overlap_color = 0;
        $missing_color = 0;
        $excess_color = 0;
        $threadhold1 = 0;
        $threadhold2 = 0;
        $threadhold3 = 0;

        $arrques = null;
        $arrans  = null;

        $questionId = intval($questionId);
        $exeId = intval($exeId);
        $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $table_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);

        // Creates a temporary Question object
        $course_id = $this->course_id;
        $objQuestionTmp = Question::read($questionId, $course_id);

        if ($objQuestionTmp === false) {
            return false;
        }

        $questionName = $objQuestionTmp->selectTitle();
        $questionWeighting = $objQuestionTmp->selectWeighting();
        $answerType = $objQuestionTmp->selectType();
        $quesId = $objQuestionTmp->selectId();
        $extra = $objQuestionTmp->extra;

        $next = 1; //not for now

        // Extra information of the question
        if (!empty($extra)) {
            $extra = explode(':', $extra);
            if ($debug) {
                error_log(print_r($extra, 1));
            }
            // Fixes problems with negatives values using intval
            $true_score = floatval(trim($extra[0]));
            $false_score = floatval(trim($extra[1]));
            $doubt_score = floatval(trim($extra[2]));
        }

        $totalWeighting = 0;
        $totalScore = 0;

        // Destruction of the Question object
        unset($objQuestionTmp);

        // Construction of the Answer object
        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

        if ($debug) {
            error_log('Count of answers: '.$nbrAnswers);
            error_log('$answerType: '.$answerType);
        }

        if ($answerType == FREE_ANSWER ||
            $answerType == ORAL_EXPRESSION ||
            $answerType == CALCULATED_ANSWER
        ) {
            $nbrAnswers = 1;
        }

        $nano = null;

        if ($answerType == ORAL_EXPRESSION) {
            $exe_info = Event::get_exercise_results_by_attempt($exeId);
            $exe_info = isset($exe_info[$exeId]) ? $exe_info[$exeId] : null;

            $params = array();
            $params['course_id'] = $course_id;
            $params['session_id'] = api_get_session_id();
            $params['user_id'] = isset($exe_info['exe_user_id'])? $exe_info['exe_user_id'] : api_get_user_id();
            $params['exercise_id'] = isset($exe_info['exe_exo_id'])? $exe_info['exe_exo_id'] : $this->id;
            $params['question_id'] = $questionId;
            $params['exe_id'] = isset($exe_info['exe_id']) ? $exe_info['exe_id'] : $exeId;

            $nano = new Nanogong($params);

            //probably this attempt came in an exercise all question by page
            if ($feedback_type == 0) {
                $nano->replace_with_real_exe($exeId);
            }
        }

        $user_answer = '';

        // Get answer list for matching
        $sql = "SELECT id_auto, id, answer
                FROM $table_ans
                WHERE c_id = $course_id AND question_id = $questionId";
        $res_answer = Database::query($sql);

        $answerMatching = array();
        while ($real_answer = Database::fetch_array($res_answer)) {
            $answerMatching[$real_answer['id_auto']] = $real_answer['answer'];
        }

        $real_answers = array();
        $quiz_question_options = Question::readQuestionOption(
            $questionId,
            $course_id
        );

        $organs_at_risk_hit = 0;
        $questionScore = 0;

        if ($debug) error_log('Start answer loop ');

        $answer_correct_array = array();

        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);
            $answerWeighting = (float)$objAnswerTmp->selectWeighting($answerId);
            $answerAutoId = $objAnswerTmp->selectAutoId($answerId);

            $answer_correct_array[$answerId] = (bool)$answerCorrect;

            if ($debug) {
                error_log("answer auto id: $answerAutoId ");
                error_log("answer correct: $answerCorrect ");
            }

            // Delineation
            $delineation_cord = $objAnswerTmp->selectHotspotCoordinates(1);
            $answer_delineation_destination=$objAnswerTmp->selectDestination(1);

            switch ($answerType) {
                // for unique answer
                case UNIQUE_ANSWER:
                case UNIQUE_ANSWER_IMAGE:
                case UNIQUE_ANSWER_NO_OPTION:
                    if ($from_database) {
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE
                                    exe_id = '".$exeId."' AND
                                    question_id= '".$questionId."'";
                        $result = Database::query($sql);
                        $choice = Database::result($result,0,"answer");

                        $studentChoice = $choice == $answerAutoId ? 1 : 0;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                            $totalScore += $answerWeighting;
                        }
                    } else {
                        $studentChoice = $choice == $answerAutoId ? 1 : 0;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                            $totalScore += $answerWeighting;
                        }
                    }
                    break;
                // for multiple answers
                case MULTIPLE_ANSWER_TRUE_FALSE:
                    if ($from_database) {
                        $choice = array();
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE
                                    exe_id = $exeId AND
                                    question_id = ".$questionId;

                        $result = Database::query($sql);
                        while ($row = Database::fetch_array($result)) {
                            $ind = $row['answer'];
                            $values = explode(':', $ind);
                            $my_answer_id = isset($values[0]) ? $values[0] : '';
                            $option = isset($values[1]) ? $values[1] : '';
                            $choice[$my_answer_id] = $option;
                        }
                    }

                    $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;

                    if (!empty($studentChoice)) {
                        if ($studentChoice == $answerCorrect) {
                            $questionScore += $true_score;
                        } else {
                            if ($quiz_question_options[$studentChoice]['name'] == "Don't know" ||
                                $quiz_question_options[$studentChoice]['name'] == "DoubtScore"
                            ) {
                                $questionScore += $doubt_score;
                            } else {
                                $questionScore += $false_score;
                            }
                        }
                    } else {
                        // If no result then the user just hit don't know
                        $studentChoice = 3;
                        $questionScore  +=  $doubt_score;
                    }
                    $totalScore = $questionScore;
                    break;
                case MULTIPLE_ANSWER: //2
                    if ($from_database) {
                        $choice = array();
                        $sql = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT."
                                WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $choice[$ind] = 1;
                        }

                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool)$studentChoice;

                        if ($studentChoice) {
                            $questionScore  +=$answerWeighting;
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool)$studentChoice;

                        if (isset($studentChoice)) {
                            $questionScore  += $answerWeighting;
                        }
                    }
                    $totalScore += $answerWeighting;

                    if ($debug) error_log("studentChoice: $studentChoice");
                    break;
                case GLOBAL_MULTIPLE_ANSWER:
                    if ($from_database) {
                        $choice = array();
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $choice[$ind] = 1;
                        }
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool)$studentChoice;
                        if ($studentChoice) {
                            $questionScore +=$answerWeighting;
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        if (isset($studentChoice)) {
                            $questionScore += $answerWeighting;
                        }
                        $real_answers[$answerId] = (bool)$studentChoice;
                    }
                    $totalScore += $answerWeighting;
                    if ($debug) error_log("studentChoice: $studentChoice");
                    break;
                case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                    if ($from_database) {
                        $sql = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT."
                                WHERE exe_id = $exeId AND question_id= ".$questionId;
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $result = explode(':',$ind);
                            if (isset($result[0])) {
                                $my_answer_id = isset($result[0]) ? $result[0] : '';
                                $option = isset($result[1]) ? $result[1] : '';
                                $choice[$my_answer_id] = $option;
                            }
                        }
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : '';

                        if ($answerCorrect == $studentChoice) {
                            //$answerCorrect = 1;
                            $real_answers[$answerId] = true;
                        } else {
                            //$answerCorrect = 0;
                            $real_answers[$answerId] = false;
                        }
                    } else {
                        $studentChoice = $choice[$answerAutoId];
                        if ($answerCorrect == $studentChoice) {
                            //$answerCorrect = 1;
                            $real_answers[$answerId] = true;
                        } else {
                            //$answerCorrect = 0;
                            $real_answers[$answerId] = false;
                        }
                    }
                    break;
                case MULTIPLE_ANSWER_COMBINATION:
                    if ($from_database) {
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = $exeId AND question_id= $questionId";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $choice[$ind] = 1;
                        }

                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;

                        if ($answerCorrect == 1) {
                            if ($studentChoice) {
                                $real_answers[$answerId] = true;
                            } else {
                                $real_answers[$answerId] = false;
                            }
                        } else {
                            if ($studentChoice) {
                                $real_answers[$answerId] = false;
                            } else {
                                $real_answers[$answerId] = true;
                            }
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;

                        if ($answerCorrect == 1) {
                            if ($studentChoice) {
                                $real_answers[$answerId] = true;
                            } else {
                                $real_answers[$answerId] = false;
                            }
                        } else {
                            if ($studentChoice) {
                                $real_answers[$answerId] = false;
                            } else {
                                $real_answers[$answerId] = true;
                            }
                        }
                    }
                    break;
                case FILL_IN_BLANKS:
                    $str = '';
                    if ($from_database) {
                        $sql = "SELECT answer
                                    FROM $TBL_TRACK_ATTEMPT
                                    WHERE
                                        exe_id = $exeId AND
                                        question_id= ".intval($questionId);
                        $result = Database::query($sql);
                        $str = Database::result($result, 0, 'answer');
                    }

                    if ($saved_results == false && strpos($str, 'font color') !== false) {
                        // the question is encoded like this
                        // [A] B [C] D [E] F::10,10,10@1
                        // number 1 before the "@" means that is a switchable fill in blank question
                        // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
                        // means that is a normal fill blank question
                        // first we explode the "::"
                        $pre_array = explode('::', $answer);

                        // is switchable fill blank or not
                        $last = count($pre_array) - 1;
                        $is_set_switchable = explode('@', $pre_array[$last]);
                        $switchable_answer_set = false;
                        if (isset ($is_set_switchable[1]) && $is_set_switchable[1] == 1) {
                            $switchable_answer_set = true;
                        }
                        $answer = '';
                        for ($k = 0; $k < $last; $k++) {
                            $answer .= $pre_array[$k];
                        }
                        // splits weightings that are joined with a comma
                        $answerWeighting = explode(',', $is_set_switchable[0]);
                        // we save the answer because it will be modified
                        $temp = $answer;
                        $answer = '';
                        $j = 0;
                        //initialise answer tags
                        $user_tags = $correct_tags = $real_text = array();
                        // the loop will stop at the end of the text
                        while (1) {
                            // quits the loop if there are no more blanks (detect '[')
                            if (($pos = api_strpos($temp, '[')) === false) {
                                // adds the end of the text
                                $answer = $temp;
                                $real_text[] = $answer;
                                break; //no more "blanks", quit the loop
                            }
                            // adds the piece of text that is before the blank
                            //and ends with '[' into a general storage array
                            $real_text[] = api_substr($temp, 0, $pos +1);
                            $answer .= api_substr($temp, 0, $pos +1);
                            //take the string remaining (after the last "[" we found)
                            $temp = api_substr($temp, $pos +1);
                            // quit the loop if there are no more blanks, and update $pos to the position of next ']'
                            if (($pos = api_strpos($temp, ']')) === false) {
                                // adds the end of the text
                                $answer .= $temp;
                                break;
                            }
                            if ($from_database) {
                                $queryfill = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT."
                                          WHERE
                                            exe_id = '".$exeId."' AND
                                            question_id= ".intval($questionId)."";
                                $resfill = Database::query($queryfill);
                                $str = Database::result($resfill, 0, 'answer');
                                api_preg_match_all('#\[([^[]*)\]#', $str, $arr);
                                $str = str_replace('\r\n', '', $str);

                                $choice = $arr[1];
                                if (isset($choice[$j])) {
                                    $tmp = api_strrpos($choice[$j], ' / ');
                                    $choice[$j] = api_substr($choice[$j], 0, $tmp);
                                    $choice[$j] = trim($choice[$j]);
                                    // Needed to let characters ' and " to work as part of an answer
                                    $choice[$j] = stripslashes($choice[$j]);
                                } else {
                                    $choice[$j] = null;
                                }
                            } else {
                                // This value is the user input, not escaped while correct answer is escaped by fckeditor
                                $choice[$j] = api_htmlentities(trim($choice[$j]));
                            }

                            $user_tags[] = $choice[$j];
                            //put the contents of the [] answer tag into correct_tags[]
                            $correct_tags[] = api_substr($temp, 0, $pos);
                            $j++;
                            $temp = api_substr($temp, $pos +1);
                        }
                        $answer = '';
                        $real_correct_tags = $correct_tags;
                        $chosen_list = array();

                        for ($i = 0; $i < count($real_correct_tags); $i++) {
                            if ($i == 0) {
                                $answer .= $real_text[0];
                            }
                            if (!$switchable_answer_set) {
                                // Needed to parse ' and " characters
                                $user_tags[$i] = stripslashes($user_tags[$i]);
                                if ($correct_tags[$i] == $user_tags[$i]) {
                                    // gives the related weighting to the student
                                    $questionScore += $answerWeighting[$i];
                                    // increments total score
                                    $totalScore += $answerWeighting[$i];
                                    // adds the word in green at the end of the string
                                    $answer .= $correct_tags[$i];
                                } elseif (!empty($user_tags[$i])) {
                                    // else if the word entered by the student IS NOT the same as the one defined by the professor
                                    // adds the word in red at the end of the string, and strikes it
                                    $answer .= '<font color="red"><s>' . $user_tags[$i] . '</s></font>';
                                } else {
                                    // adds a tabulation if no word has been typed by the student
                                    $answer .= ''; // remove &nbsp; that causes issue
                                }
                            } else {
                                // switchable fill in the blanks
                                if (in_array($user_tags[$i], $correct_tags)) {
                                    $chosen_list[] = $user_tags[$i];
                                    $correct_tags = array_diff($correct_tags, $chosen_list);
                                    // gives the related weighting to the student
                                    $questionScore += $answerWeighting[$i];
                                    // increments total score
                                    $totalScore += $answerWeighting[$i];
                                    // adds the word in green at the end of the string
                                    $answer .= $user_tags[$i];
                                } elseif (!empty ($user_tags[$i])) {
                                    // else if the word entered by the student IS NOT the same as the one defined by the professor
                                    // adds the word in red at the end of the string, and strikes it
                                    $answer .= '<font color="red"><s>' . $user_tags[$i] . '</s></font>';
                                } else {
                                    // adds a tabulation if no word has been typed by the student
                                    $answer .= '';  // remove &nbsp; that causes issue
                                }
                            }

                            // adds the correct word, followed by ] to close the blank
                            $answer .= ' / <font color="green"><b>' . $real_correct_tags[$i] . '</b></font>]';
                            if (isset($real_text[$i +1])) {
                                $answer .= $real_text[$i +1];
                            }
                        }
                    } else {
                        // insert the student result in the track_e_attempt table, field answer
                        // $answer is the answer like in the c_quiz_answer table for the question
                        // student data are choice[]
                        $listCorrectAnswers = FillBlanks::getAnswerInfo(
                            $answer
                        );
                        $switchableAnswerSet = $listCorrectAnswers["switchable"];
                        $answerWeighting = $listCorrectAnswers["tabweighting"];
                        // user choices is an array $choice

                        // get existing user data in n the BDD
                        if ($from_database) {
                            $sql = "SELECT answer
                                    FROM $TBL_TRACK_ATTEMPT
                                    WHERE
                                        exe_id = $exeId AND
                                        question_id= ".intval($questionId);
                            $result = Database::query($sql);
                            $str = Database::result($result, 0, 'answer');
                            $listStudentResults = FillBlanks::getAnswerInfo(
                                $str,
                                true
                            );
                            $choice = $listStudentResults['studentanswer'];
                        }

                        // loop other all blanks words
                        if (!$switchableAnswerSet) {
                            // not switchable answer, must be in the same place than teacher order
                            for ($i = 0; $i < count(
                                $listCorrectAnswers['tabwords']
                            ); $i++) {
                                $studentAnswer = isset($choice[$i]) ? trim(
                                    $choice[$i]
                                ) : '';

                                // This value is the user input, not escaped while correct answer is escaped by fckeditor
                                // Works with cyrillic alphabet and when using ">" chars see #7718 #7610 #7618
                                if (!$from_database) {
                                    $studentAnswer = htmlentities(
                                        api_utf8_encode($studentAnswer)
                                    );
                                }

                                $correctAnswer = $listCorrectAnswers['tabwords'][$i];
                                $isAnswerCorrect = 0;
                                if (FillBlanks::isGoodStudentAnswer(
                                    $studentAnswer,
                                    $correctAnswer
                                )
                                ) {
                                    // gives the related weighting to the student
                                    $questionScore += $answerWeighting[$i];
                                    // increments total score
                                    $totalScore += $answerWeighting[$i];
                                    $isAnswerCorrect = 1;
                                }
                                $listCorrectAnswers['studentanswer'][$i] = $studentAnswer;
                                $listCorrectAnswers['studentscore'][$i] = $isAnswerCorrect;
                            }
                        } else {
                            // switchable answer
                            $listStudentAnswerTemp = $choice;
                            $listTeacherAnswerTemp = $listCorrectAnswers['tabwords'];
                            // for every teacher answer, check if there is a student answer
                            for ($i = 0; $i < count(
                                $listStudentAnswerTemp
                            ); $i++) {
                                $studentAnswer = trim(
                                    $listStudentAnswerTemp[$i]
                                );
                                $found = false;
                                for ($j = 0; $j < count(
                                    $listTeacherAnswerTemp
                                ); $j++) {
                                    $correctAnswer = $listTeacherAnswerTemp[$j];
                                    if (!$found) {
                                        if (FillBlanks::isGoodStudentAnswer(
                                            $studentAnswer,
                                            $correctAnswer
                                        )
                                        ) {
                                            $questionScore += $answerWeighting[$i];
                                            $totalScore += $answerWeighting[$i];
                                            $listTeacherAnswerTemp[$j] = "";
                                            $found = true;
                                        }
                                    }
                                }
                                $listCorrectAnswers['studentanswer'][$i] = $studentAnswer;
                                if (!$found) {
                                    $listCorrectAnswers['studentscore'][$i] = 0;
                                } else {
                                    $listCorrectAnswers['studentscore'][$i] = 1;
                                }
                            }
                        }
                        $answer = FillBlanks::getAnswerInStudentAttempt(
                            $listCorrectAnswers
                        );
                    }

                    break;
                // for calculated answer
                case CALCULATED_ANSWER:
                    $answer = $objAnswerTmp->selectAnswer($_SESSION['calculatedAnswerId'][$questionId]);
                    $preArray = explode('@@', $answer);
                    $last = count($preArray) - 1;
                    $answer = '';
                    for ($k = 0; $k < $last; $k++) {
                        $answer .= $preArray[$k];
                    }
                    $answerWeighting = array($answerWeighting);
                    // we save the answer because it will be modified
                    $temp = $answer;
                    $answer = '';
                    $j = 0;
                    //initialise answer tags
                    $userTags = $correctTags = $realText = array();
                    // the loop will stop at the end of the text
                    while (1) {
                        // quits the loop if there are no more blanks (detect '[')
                        if (($pos = api_strpos($temp, '[')) === false) {
                            // adds the end of the text
                            $answer = $temp;
                            $realText[] = $answer;
                            break; //no more "blanks", quit the loop
                        }
                        // adds the piece of text that is before the blank
                        //and ends with '[' into a general storage array
                        $realText[] = api_substr($temp, 0, $pos +1);
                        $answer .= api_substr($temp, 0, $pos +1);
                        //take the string remaining (after the last "[" we found)
                        $temp = api_substr($temp, $pos +1);
                        // quit the loop if there are no more blanks, and update $pos to the position of next ']'
                        if (($pos = api_strpos($temp, ']')) === false) {
                            // adds the end of the text
                            $answer .= $temp;
                            break;
                        }
                        if ($from_database) {
                            $queryfill = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT."
                                          WHERE
                                            exe_id = '".$exeId."' AND
                                            question_id= ".intval($questionId)."";
                            $resfill = Database::query($queryfill);
                            $str = Database::result($resfill, 0, 'answer');
                            api_preg_match_all('#\[([^[]*)\]#', $str, $arr);
                            $str = str_replace('\r\n', '', $str);
                            $choice = $arr[1];
                            if (isset($choice[$j])) {
                                $tmp = api_strrpos($choice[$j], ' / ');
                                $choice[$j] = api_substr($choice[$j], 0, $tmp);
                                $choice[$j] = trim($choice[$j]);
                                // Needed to let characters ' and " to work as part of an answer
                                $choice[$j] = stripslashes($choice[$j]);
                            } else {
                                $choice[$j] = null;
                            }
                        } else {
                            // This value is the user input, not escaped while correct answer is escaped by fckeditor
                            $choice[$j] = api_htmlentities(trim($choice[$j]));
                        }
                        $userTags[] = $choice[$j];
                        //put the contents of the [] answer tag into correct_tags[]
                        $correctTags[] = api_substr($temp, 0, $pos);
                        $j++;
                        $temp = api_substr($temp, $pos +1);
                    }
                    $answer = '';
                    $realCorrectTags = $correctTags;
                    for ($i = 0; $i < count($realCorrectTags); $i++) {
                        if ($i == 0) {
                            $answer .= $realText[0];
                        }
                        // Needed to parse ' and " characters
                        $userTags[$i] = stripslashes($userTags[$i]);
                        if ($correctTags[$i] == $userTags[$i]) {
                            // gives the related weighting to the student
                            $questionScore += $answerWeighting[$i];
                            // increments total score
                            $totalScore += $answerWeighting[$i];
                            // adds the word in green at the end of the string
                            $answer .= $correctTags[$i];
                        } elseif (!empty($userTags[$i])) {
                            // else if the word entered by the student IS NOT the same as the one defined by the professor
                            // adds the word in red at the end of the string, and strikes it
                            $answer .= '<font color="red"><s>' . $userTags[$i] . '</s></font>';
                        } else {
                            // adds a tabulation if no word has been typed by the student
                            $answer .= ''; // remove &nbsp; that causes issue
                        }
                        // adds the correct word, followed by ] to close the blank
                        $answer .= ' / <font color="green"><b>' . $realCorrectTags[$i] . '</b></font>]';
                        if (isset($realText[$i +1])) {
                            $answer .= $realText[$i +1];
                        }
                    }
                    break;
                // for free answer
                case FREE_ANSWER:
                    if ($from_database) {
                        $query  = "SELECT answer, marks FROM ".$TBL_TRACK_ATTEMPT."
                                   WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resq = Database::query($query);
                        $data = Database::fetch_array($resq);

                        $choice = $data['answer'];
                        $choice = str_replace('\r\n', '', $choice);
                        $choice = stripslashes($choice);
                        $questionScore = $data['marks'];

                        if ($questionScore == -1) {
                            $totalScore+= 0;
                        } else {
                            $totalScore+= $questionScore;
                        }
                        if ($questionScore == '') {
                            $questionScore = 0;
                        }
                        $arrques = $questionName;
                        $arrans  = $choice;
                    } else {
                        $studentChoice = $choice;
                        if ($studentChoice) {
                            //Fixing negative puntation see #2193
                            $questionScore = 0;
                            $totalScore += 0;
                        }
                    }
                    break;
                case ORAL_EXPRESSION:
                    if ($from_database) {
                        $query  = "SELECT answer, marks FROM ".$TBL_TRACK_ATTEMPT."
                                   WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resq   = Database::query($query);
                        $choice = Database::result($resq,0,'answer');
                        $choice = str_replace('\r\n', '', $choice);
                        $choice = stripslashes($choice);
                        $questionScore = Database::result($resq,0,"marks");
                        if ($questionScore==-1) {
                            $totalScore+=0;
                        } else {
                            $totalScore+=$questionScore;
                        }
                        $arrques = $questionName;
                        $arrans  = $choice;
                    } else {
                        $studentChoice = $choice;
                        if ($studentChoice) {
                            //Fixing negative puntation see #2193
                            $questionScore = 0;
                            $totalScore += 0;
                        }
                    }
                    break;
                case DRAGGABLE:
                    //no break
                case MATCHING_DRAGGABLE:
                    //no break
                case MATCHING:
                    if ($from_database) {
                        $sql = 'SELECT id, answer, id_auto
                                FROM '.$table_ans.'
                                WHERE
                                    c_id = '.$course_id.' AND
                                    question_id = "'.$questionId.'" AND
                                    correct = 0';
                        $res_answer = Database::query($sql);
                        // Getting the real answer
                        $real_list = array();
                        while ($real_answer = Database::fetch_array($res_answer)) {
                            $real_list[$real_answer['id_auto']] = $real_answer['answer'];
                        }

                        $sql = 'SELECT id, answer, correct, id_auto, ponderation
                                FROM '.$table_ans.'
                                WHERE
                                    c_id = '.$course_id.' AND
                                    question_id="'.$questionId.'" AND
                                    correct <> 0
                                ORDER BY id_auto';
                        $res_answers = Database::query($sql);

                        $questionScore = 0;

                        while ($a_answers = Database::fetch_array($res_answers)) {
                            $i_answer_id = $a_answers['id']; //3
                            $s_answer_label = $a_answers['answer'];  // your daddy - your mother
                            $i_answer_correct_answer = $a_answers['correct']; //1 - 2
                            $i_answer_id_auto = $a_answers['id_auto']; // 3 - 4

                            $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                    WHERE
                                        exe_id = '$exeId' AND
                                        question_id = '$questionId' AND
                                        position = '$i_answer_id_auto'";

                            $res_user_answer = Database::query($sql);

                            if (Database::num_rows($res_user_answer) > 0) {
                                //  rich - good looking
                                $s_user_answer = Database::result($res_user_answer, 0, 0);
                            } else {
                                $s_user_answer = 0;
                            }

                            $i_answerWeighting = $a_answers['ponderation'];

                            $user_answer = '';
                            if (!empty($s_user_answer)) {
                                if ($answerType == DRAGGABLE) {
                                    if ($s_user_answer == $i_answer_correct_answer) {
                                        $questionScore += $i_answerWeighting;
                                        $totalScore += $i_answerWeighting;
                                        $user_answer = Display::label(get_lang('Correct'), 'success');
                                    } else {
                                        $user_answer = Display::label(get_lang('Incorrect'), 'danger');
                                    }
                                } else {
                                    if ($s_user_answer == $i_answer_correct_answer) {
                                        $questionScore += $i_answerWeighting;
                                        $totalScore += $i_answerWeighting;

                                        if (isset($real_list[$i_answer_id])) {
                                            $user_answer = Display::span($real_list[$i_answer_id]);
                                        }
                                    } else {
                                        $user_answer = Display::span(
                                            $real_list[$s_user_answer],
                                            ['style' => 'color: #FF0000; text-decoration: line-through;']
                                        );
                                    }
                                }
                            } elseif ($answerType == DRAGGABLE) {
                                $user_answer = Display::label(get_lang('Incorrect'), 'danger');
                            }

                            if ($show_result) {
                                echo '<tr>';
                                echo '<td>' . $s_answer_label . '</td>';
                                echo '<td>' . $user_answer;

                                if (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                                    if (isset($real_list[$i_answer_correct_answer])) {
                                        echo Display::span(
                                            $real_list[$i_answer_correct_answer],
                                            ['style' => 'color: #008000; font-weight: bold;']
                                        );
                                    }
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        break(2); // break the switch and the "for" condition
                    } else {
                        if ($answerCorrect) {
                            if (isset($choice[$answerAutoId]) &&
                                $answerCorrect == $choice[$answerAutoId]
                            ) {
                                $questionScore += $answerWeighting;
                                $totalScore += $answerWeighting;
                                $user_answer = Display::span($answerMatching[$choice[$answerAutoId]]);
                            } else {
                                if (isset($answerMatching[$choice[$answerAutoId]])) {
                                    $user_answer = Display::span(
                                        $answerMatching[$choice[$answerAutoId]],
                                        ['style' => 'color: #FF0000; text-decoration: line-through;']
                                    );
                                }
                            }
                            $matching[$answerAutoId] = $choice[$answerAutoId];
                        }
                        break;
                    }
                case HOT_SPOT:
                    if ($from_database) {
                        $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                        $sql = "SELECT hotspot_correct
                                FROM $TBL_TRACK_HOTSPOT
                                WHERE
                                    hotspot_exe_id = '".$exeId."' AND
                                    hotspot_question_id= '".$questionId."' AND
                                    hotspot_answer_id = ".intval($answerAutoId)."";
                        $result = Database::query($sql);
                        $studentChoice = Database::result($result, 0, "hotspot_correct");

                        if ($studentChoice) {
                            $questionScore  += $answerWeighting;
                            $totalScore     += $answerWeighting;
                        }
                    } else {
                        if (!isset($choice[$answerAutoId])) {
                            $choice[$answerAutoId] = 0;
                        } else {
                            $studentChoice = $choice[$answerAutoId];

                            $choiceIsValid = false;

                            if (!empty($studentChoice)) {
                                $hotspotType = $objAnswerTmp->selectHotspotType($answerId);
                                $hotspotCoordinates = $objAnswerTmp->selectHotspotCoordinates($answerId);
                                $choicePoint = Geometry::decodePoint($studentChoice);

                                switch ($hotspotType) {
                                    case 'square':
                                        $hotspotProperties = Geometry::decodeSquare($hotspotCoordinates);
                                        $choiceIsValid = Geometry::pointIsInSquare($hotspotProperties, $choicePoint);
                                        break;

                                    case 'circle':
                                        $hotspotProperties = Geometry::decodeEllipse($hotspotCoordinates);
                                        $choiceIsValid = Geometry::pointIsInEllipse($hotspotProperties, $choicePoint);
                                        break;

                                    case 'poly':
                                        $hotspotProperties = Geometry::decodePolygon($hotspotCoordinates);
                                        $choiceIsValid = Geometry::pointIsInPolygon($hotspotProperties, $choicePoint);
                                        break;
                                }
                            }

                            $choice[$answerAutoId] = 0;

                            if ($choiceIsValid) {
                                $questionScore  += $answerWeighting;
                                $totalScore     += $answerWeighting;
                                $choice[$answerAutoId] = 1;
                            }
                        }
                    }
                    break;
                // @todo never added to chamilo
                //for hotspot with fixed order
                case HOT_SPOT_ORDER :
                    $studentChoice = $choice['order'][$answerId];
                    if ($studentChoice == $answerId) {
                        $questionScore  += $answerWeighting;
                        $totalScore     += $answerWeighting;
                        $studentChoice = true;
                    } else {
                        $studentChoice = false;
                    }
                    break;
                // for hotspot with delineation
                case HOT_SPOT_DELINEATION :
                    if ($from_database) {
                        // getting the user answer
                        $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                        $query   = "SELECT hotspot_correct, hotspot_coordinate
                                    FROM $TBL_TRACK_HOTSPOT
                                    WHERE
                                        hotspot_exe_id = '".$exeId."' AND
                                        hotspot_question_id= '".$questionId."' AND
                                        hotspot_answer_id='1'";
                        //by default we take 1 because it's a delineation
                        $resq = Database::query($query);
                        $row = Database::fetch_array($resq,'ASSOC');

                        $choice = $row['hotspot_correct'];
                        $user_answer = $row['hotspot_coordinate'];

                        // THIS is very important otherwise the poly_compile will throw an error!!
                        // round-up the coordinates
                        $coords = explode('/',$user_answer);
                        $user_array = '';
                        foreach ($coords as $coord) {
                            list($x,$y) = explode(';',$coord);
                            $user_array .= round($x).';'.round($y).'/';
                        }
                        $user_array = substr($user_array,0,-1);
                    } else {
                        if (!empty($studentChoice)) {
                            $newquestionList[] = $questionId;
                        }

                        if ($answerId === 1) {
                            $studentChoice = $choice[$answerId];
                            $questionScore += $answerWeighting;

                            if ($hotspot_delineation_result[1]==1) {
                                $totalScore += $answerWeighting; //adding the total
                            }
                        }
                    }
                    $_SESSION['hotspot_coord'][1]	= $delineation_cord;
                    $_SESSION['hotspot_dest'][1]	= $answer_delineation_destination;
                    break;
            } // end switch Answertype

            if ($show_result) {
                if ($debug) error_log('show result '.$show_result);
                if ($from == 'exercise_result') {
                    if ($debug) error_log('Showing questions $from '.$from);
                    //display answers (if not matching type, or if the answer is correct)
                    if (
                        !in_array(
                            $answerType,
                            [MATCHING, DRAGGABLE, MATCHING_DRAGGABLE]
                        ) ||
                        $answerCorrect
                    ) {
                        if (
                            in_array(
                                $answerType,
                                array(
                                    UNIQUE_ANSWER,
                                    UNIQUE_ANSWER_IMAGE,
                                    UNIQUE_ANSWER_NO_OPTION,
                                    MULTIPLE_ANSWER,
                                    MULTIPLE_ANSWER_COMBINATION,
                                    GLOBAL_MULTIPLE_ANSWER
                                )
                            )
                        ) {
                            //if ($origin != 'learnpath') {
                            ExerciseShowFunctions::display_unique_or_multiple_answer(
                                $feedback_type,
                                $answerType,
                                $studentChoice,
                                $answer,
                                $answerComment,
                                $answerCorrect,
                                0,
                                0,
                                0,
                                $results_disabled
                            );
                            //}
                        } elseif ($answerType == MULTIPLE_ANSWER_TRUE_FALSE) {
                            //if ($origin!='learnpath') {
                            ExerciseShowFunctions::display_multiple_answer_true_false(
                                $feedback_type,
                                $answerType,
                                $studentChoice,
                                $answer,
                                $answerComment,
                                $answerCorrect,
                                0,
                                $questionId,
                                0,
                                $results_disabled
                            );
                            //}
                        } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE ) {
                            //	if ($origin!='learnpath') {
                            ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                $feedback_type,
                                $answerType,
                                $studentChoice,
                                $answer,
                                $answerComment,
                                $answerCorrect,
                                0,
                                0,
                                0,
                                $results_disabled
                            );
                            //}
                        } elseif ($answerType == FILL_IN_BLANKS) {
                            //if ($origin!='learnpath') {
                            ExerciseShowFunctions::display_fill_in_blanks_answer($feedback_type, $answer,0,0, $results_disabled);
                            //	}
                        } elseif ($answerType == CALCULATED_ANSWER) {
                            //if ($origin!='learnpath') {
                            ExerciseShowFunctions::display_calculated_answer($feedback_type, $answer,0,0);
                            //  }
                        } elseif ($answerType == FREE_ANSWER) {
                            //if($origin != 'learnpath') {
                            ExerciseShowFunctions::display_free_answer(
                                $feedback_type,
                                $choice,
                                $exeId,
                                $questionId,
                                $questionScore
                            );
                            //}
                        } elseif ($answerType == ORAL_EXPRESSION) {
                            // to store the details of open questions in an array to be used in mail
                            //if ($origin != 'learnpath') {
                            ExerciseShowFunctions::display_oral_expression_answer(
                                $feedback_type,
                                $choice,
                                0,
                                0,
                                $nano);
                            //}
                        } elseif ($answerType == HOT_SPOT) {
                            //if ($origin != 'learnpath') {
                            ExerciseShowFunctions::display_hotspot_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment,
                                $results_disabled
                            );
                            //	}
                        } elseif ($answerType == HOT_SPOT_ORDER) {
                            //if ($origin != 'learnpath') {
                            ExerciseShowFunctions::display_hotspot_order_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment
                            );
                            //}
                        } elseif ($answerType == HOT_SPOT_DELINEATION) {
                            $user_answer = $_SESSION['exerciseResultCoordinates'][$questionId];

                            //round-up the coordinates
                            $coords = explode('/',$user_answer);
                            $user_array = '';
                            foreach ($coords as $coord) {
                                list($x,$y) = explode(';',$coord);
                                $user_array .= round($x).';'.round($y).'/';
                            }
                            $user_array = substr($user_array,0,-1);

                            if ($next) {

                                $user_answer = $user_array;

                                // we compare only the delineation not the other points
                                $answer_question = $_SESSION['hotspot_coord'][1];
                                $answerDestination = $_SESSION['hotspot_dest'][1];

                                //calculating the area
                                $poly_user = convert_coordinates($user_answer, '/');
                                $poly_answer = convert_coordinates($answer_question, '|');
                                $max_coord = poly_get_max($poly_user, $poly_answer);
                                $poly_user_compiled = poly_compile($poly_user, $max_coord);
                                $poly_answer_compiled = poly_compile($poly_answer, $max_coord);
                                $poly_results = poly_result($poly_answer_compiled, $poly_user_compiled, $max_coord);

                                $overlap = $poly_results['both'];
                                $poly_answer_area = $poly_results['s1'];
                                $poly_user_area = $poly_results['s2'];
                                $missing = $poly_results['s1Only'];
                                $excess = $poly_results['s2Only'];

                                //$overlap = round(polygons_overlap($poly_answer,$poly_user));
                                // //this is an area in pixels
                                if ($debug > 0) {
                                    error_log(__LINE__ . ' - Polygons results are ' . print_r($poly_results, 1), 0);
                                }

                                if ($overlap < 1) {
                                    //shortcut to avoid complicated calculations
                                    $final_overlap = 0;
                                    $final_missing = 100;
                                    $final_excess = 100;
                                } else {
                                    // the final overlap is the percentage of the initial polygon
                                    // that is overlapped by the user's polygon
                                    $final_overlap = round(((float) $overlap / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final overlap is ' . $final_overlap, 0);
                                    }
                                    // the final missing area is the percentage of the initial polygon
                                    // that is not overlapped by the user's polygon
                                    $final_missing = 100 - $final_overlap;
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final missing is ' . $final_missing, 0);
                                    }
                                    // the final excess area is the percentage of the initial polygon's size
                                    // that is covered by the user's polygon outside of the initial polygon
                                    $final_excess = round((((float) $poly_user_area - (float) $overlap) / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final excess is ' . $final_excess, 0);
                                    }
                                }

                                //checking the destination parameters parsing the "@@"
                                $destination_items= explode('@@', $answerDestination);
                                $threadhold_total = $destination_items[0];
                                $threadhold_items=explode(';',$threadhold_total);
                                $threadhold1 = $threadhold_items[0]; // overlap
                                $threadhold2 = $threadhold_items[1]; // excess
                                $threadhold3 = $threadhold_items[2];	 //missing

                                // if is delineation
                                if ($answerId===1) {
                                    //setting colors
                                    if ($final_overlap>=$threadhold1) {
                                        $overlap_color=true; //echo 'a';
                                    }
                                    //echo $excess.'-'.$threadhold2;
                                    if ($final_excess<=$threadhold2) {
                                        $excess_color=true; //echo 'b';
                                    }
                                    //echo '--------'.$missing.'-'.$threadhold3;
                                    if ($final_missing<=$threadhold3) {
                                        $missing_color=true; //echo 'c';
                                    }

                                    // if pass
                                    if (
                                        $final_overlap >= $threadhold1 &&
                                        $final_missing <= $threadhold3 &&
                                        $final_excess <= $threadhold2
                                    ) {
                                        $next=1; //go to the oars
                                        $result_comment=get_lang('Acceptable');
                                        $final_answer = 1;	// do not update with  update_exercise_attempt
                                    } else {
                                        $next=0;
                                        $result_comment=get_lang('Unacceptable');
                                        $comment=$answerDestination=$objAnswerTmp->selectComment(1);
                                        $answerDestination=$objAnswerTmp->selectDestination(1);
                                        //checking the destination parameters parsing the "@@"
                                        $destination_items= explode('@@', $answerDestination);
                                    }
                                } elseif($answerId>1) {
                                    if ($objAnswerTmp->selectHotspotType($answerId) == 'noerror') {
                                        if ($debug>0) {
                                            error_log(__LINE__.' - answerId is of type noerror',0);
                                        }
                                        //type no error shouldn't be treated
                                        $next = 1;
                                        continue;
                                    }
                                    if ($debug>0) {
                                        error_log(__LINE__.' - answerId is >1 so we\'re probably in OAR',0);
                                    }
                                    //check the intersection between the oar and the user
                                    //echo 'user';	print_r($x_user_list);		print_r($y_user_list);
                                    //echo 'official';print_r($x_list);print_r($y_list);
                                    //$result = get_intersection_data($x_list,$y_list,$x_user_list,$y_user_list);
                                    $inter= $result['success'];

                                    //$delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);
                                    $delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);

                                    $poly_answer = convert_coordinates($delineation_cord,'|');
                                    $max_coord = poly_get_max($poly_user,$poly_answer);
                                    $poly_answer_compiled = poly_compile($poly_answer,$max_coord);
                                    $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled,$max_coord);

                                    if ($overlap == false) {
                                        //all good, no overlap
                                        $next = 1;
                                        continue;
                                    } else {
                                        if ($debug>0) {
                                            error_log(__LINE__.' - Overlap is '.$overlap.': OAR hit',0);
                                        }
                                        $organs_at_risk_hit++;
                                        //show the feedback
                                        $next=0;
                                        $comment=$answerDestination=$objAnswerTmp->selectComment($answerId);
                                        $answerDestination=$objAnswerTmp->selectDestination($answerId);

                                        $destination_items= explode('@@', $answerDestination);
                                        $try_hotspot=$destination_items[1];
                                        $lp_hotspot=$destination_items[2];
                                        $select_question_hotspot=$destination_items[3];
                                        $url_hotspot=$destination_items[4];
                                    }
                                }
                            } else {	// the first delineation feedback
                                if ($debug>0) {
                                    error_log(__LINE__.' first',0);
                                }
                            }
                        } elseif (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                            echo '<tr>';
                            echo Display::tag('td', $answerMatching[$answerId]);
                            echo Display::tag(
                                'td',
                                "$user_answer / " . Display::tag(
                                    'strong',
                                    $answerMatching[$answerCorrect],
                                    ['style' => 'color: #008000; font-weight: bold;']
                                )
                            );
                            echo '</tr>';
                        }
                    }
                } else {
                    if ($debug) error_log('Showing questions $from '.$from);

                    switch ($answerType) {
                        case UNIQUE_ANSWER:
                        case UNIQUE_ANSWER_IMAGE:
                        case UNIQUE_ANSWER_NO_OPTION:
                        case MULTIPLE_ANSWER:
                        case GLOBAL_MULTIPLE_ANSWER :
                        case MULTIPLE_ANSWER_COMBINATION:
                            if ($answerId == 1) {
                                ExerciseShowFunctions::display_unique_or_multiple_answer(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    $answerId,
                                    $results_disabled
                                );
                            } else {
                                ExerciseShowFunctions::display_unique_or_multiple_answer(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    "",
                                    $results_disabled
                                );
                            }
                            break;
                        case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                            if ($answerId == 1) {
                                ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    $answerId,
                                    $results_disabled
                                );
                            } else {
                                ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    "",
                                    $results_disabled
                                );
                            }
                            break;
                        case MULTIPLE_ANSWER_TRUE_FALSE:
                            if ($answerId == 1) {
                                ExerciseShowFunctions::display_multiple_answer_true_false(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    $answerId,
                                    $results_disabled
                                );
                            } else {
                                ExerciseShowFunctions::display_multiple_answer_true_false(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    "",
                                    $results_disabled
                                );
                            }
                            break;
                        case FILL_IN_BLANKS:
                            ExerciseShowFunctions::display_fill_in_blanks_answer(
                                $feedback_type,
                                $answer,
                                $exeId,
                                $questionId,
                                $results_disabled,
                                $str
                            );
                            break;
                        case CALCULATED_ANSWER:
                            ExerciseShowFunctions::display_calculated_answer(
                                $feedback_type,
                                $answer,
                                $exeId,
                                $questionId
                            );
                            break;
                        case FREE_ANSWER:
                            echo ExerciseShowFunctions::display_free_answer(
                                $feedback_type,
                                $choice,
                                $exeId,
                                $questionId,
                                $questionScore
                            );
                            break;
                        case ORAL_EXPRESSION:
                            echo '<tr>
                                <td valign="top">' . ExerciseShowFunctions::display_oral_expression_answer(
                                    $feedback_type,
                                    $choice,
                                    $exeId,
                                    $questionId,
                                    $nano
                                ) . '</td>
                                </tr>
                                </table>';
                            break;
                        case HOT_SPOT:
                            ExerciseShowFunctions::display_hotspot_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment,
                                $results_disabled);
                            break;
                        case HOT_SPOT_DELINEATION:
                            $user_answer = $user_array;
                            if ($next) {
                                //$tbl_track_e_hotspot = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                                // Save into db
                                /*	$sql = "INSERT INTO $tbl_track_e_hotspot (
                                 * hotspot_user_id,
                                 *  hotspot_course_code,
                                 *  hotspot_exe_id,
                                 *  hotspot_question_id,
                                 *  hotspot_answer_id,
                                 *  hotspot_correct,
                                 *  hotspot_coordinate
                                 *  )
                                VALUES (
                                 * '".Database::escape_string($_user['user_id'])."',
                                 *  '".Database::escape_string($_course['id'])."',
                                 *  '".Database::escape_string($exeId)."', '".Database::escape_string($questionId)."',
                                 *  '".Database::escape_string($answerId)."',
                                 *  '".Database::escape_string($studentChoice)."',
                                 *  '".Database::escape_string($user_array)."')";
                                $result = Database::query($sql,__FILE__,__LINE__);
                                 */
                                $user_answer = $user_array;

                                // we compare only the delineation not the other points
                                $answer_question = $_SESSION['hotspot_coord'][1];
                                $answerDestination = $_SESSION['hotspot_dest'][1];

                                //calculating the area
                                $poly_user = convert_coordinates($user_answer, '/');
                                $poly_answer = convert_coordinates($answer_question, '|');

                                $max_coord = poly_get_max($poly_user, $poly_answer);
                                $poly_user_compiled = poly_compile($poly_user, $max_coord);
                                $poly_answer_compiled = poly_compile($poly_answer, $max_coord);
                                $poly_results = poly_result($poly_answer_compiled, $poly_user_compiled, $max_coord);

                                $overlap = $poly_results['both'];
                                $poly_answer_area = $poly_results['s1'];
                                $poly_user_area = $poly_results['s2'];
                                $missing = $poly_results['s1Only'];
                                $excess = $poly_results['s2Only'];

                                //$overlap = round(polygons_overlap($poly_answer,$poly_user)); //this is an area in pixels
                                if ($debug > 0) {
                                    error_log(__LINE__ . ' - Polygons results are ' . print_r($poly_results, 1), 0);
                                }
                                if ($overlap < 1) {
                                    //shortcut to avoid complicated calculations
                                    $final_overlap = 0;
                                    $final_missing = 100;
                                    $final_excess = 100;
                                } else {
                                    // the final overlap is the percentage of the initial polygon that is overlapped by the user's polygon
                                    $final_overlap = round(((float) $overlap / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final overlap is ' . $final_overlap, 0);
                                    }
                                    // the final missing area is the percentage of the initial polygon that is not overlapped by the user's polygon
                                    $final_missing = 100 - $final_overlap;
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final missing is ' . $final_missing, 0);
                                    }
                                    // the final excess area is the percentage of the initial polygon's size that is covered by the user's polygon outside of the initial polygon
                                    $final_excess = round((((float) $poly_user_area - (float) $overlap) / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final excess is ' . $final_excess, 0);
                                    }
                                }

                                //checking the destination parameters parsing the "@@"
                                $destination_items = explode('@@', $answerDestination);
                                $threadhold_total = $destination_items[0];
                                $threadhold_items = explode(';', $threadhold_total);
                                $threadhold1 = $threadhold_items[0]; // overlap
                                $threadhold2 = $threadhold_items[1]; // excess
                                $threadhold3 = $threadhold_items[2];  //missing
                                // if is delineation
                                if ($answerId === 1) {
                                    //setting colors
                                    if ($final_overlap >= $threadhold1) {
                                        $overlap_color = true; //echo 'a';
                                    }
                                    //echo $excess.'-'.$threadhold2;
                                    if ($final_excess <= $threadhold2) {
                                        $excess_color = true; //echo 'b';
                                    }
                                    //echo '--------'.$missing.'-'.$threadhold3;
                                    if ($final_missing <= $threadhold3) {
                                        $missing_color = true; //echo 'c';
                                    }

                                    // if pass
                                    if ($final_overlap >= $threadhold1 && $final_missing <= $threadhold3 && $final_excess <= $threadhold2) {
                                        $next = 1; //go to the oars
                                        $result_comment = get_lang('Acceptable');
                                        $final_answer = 1; // do not update with  update_exercise_attempt
                                    } else {
                                        $next = 0;
                                        $result_comment = get_lang('Unacceptable');
                                        $comment = $answerDestination = $objAnswerTmp->selectComment(1);
                                        $answerDestination = $objAnswerTmp->selectDestination(1);
                                        //checking the destination parameters parsing the "@@"
                                        $destination_items = explode('@@', $answerDestination);
                                    }
                                } elseif ($answerId > 1) {
                                    if ($objAnswerTmp->selectHotspotType($answerId) == 'noerror') {
                                        if ($debug > 0) {
                                            error_log(__LINE__ . ' - answerId is of type noerror', 0);
                                        }
                                        //type no error shouldn't be treated
                                        $next = 1;
                                        continue;
                                    }
                                    if ($debug > 0) {
                                        error_log(__LINE__ . ' - answerId is >1 so we\'re probably in OAR', 0);
                                    }
                                    //check the intersection between the oar and the user
                                    //echo 'user';	print_r($x_user_list);		print_r($y_user_list);
                                    //echo 'official';print_r($x_list);print_r($y_list);
                                    //$result = get_intersection_data($x_list,$y_list,$x_user_list,$y_user_list);
                                    $inter = $result['success'];

                                    //$delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);
                                    $delineation_cord = $objAnswerTmp->selectHotspotCoordinates($answerId);

                                    $poly_answer = convert_coordinates($delineation_cord, '|');
                                    $max_coord = poly_get_max($poly_user, $poly_answer);
                                    $poly_answer_compiled = poly_compile($poly_answer, $max_coord);
                                    $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled,$max_coord);

                                    if ($overlap == false) {
                                        //all good, no overlap
                                        $next = 1;
                                        continue;
                                    } else {
                                        if ($debug > 0) {
                                            error_log(__LINE__ . ' - Overlap is ' . $overlap . ': OAR hit', 0);
                                        }
                                        $organs_at_risk_hit++;
                                        //show the feedback
                                        $next = 0;
                                        $comment = $answerDestination = $objAnswerTmp->selectComment($answerId);
                                        $answerDestination = $objAnswerTmp->selectDestination($answerId);

                                        $destination_items = explode('@@', $answerDestination);
                                        $try_hotspot = $destination_items[1];
                                        $lp_hotspot = $destination_items[2];
                                        $select_question_hotspot = $destination_items[3];
                                        $url_hotspot=$destination_items[4];
                                    }
                                }
                            } else {	// the first delineation feedback
                                if ($debug > 0) {
                                    error_log(__LINE__ . ' first', 0);
                                }
                            }
                            break;
                        case HOT_SPOT_ORDER:
                            ExerciseShowFunctions::display_hotspot_order_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment
                            );
                            break;
                        case DRAGGABLE:
                            //no break
                        case MATCHING_DRAGGABLE:
                            //no break
                        case MATCHING:
                            echo '<tr>';
                            echo Display::tag('td', $answerMatching[$answerId]);
                            echo Display::tag(
                                'td',
                                "$user_answer / " . Display::tag(
                                    'strong',
                                    $answerMatching[$answerCorrect],
                                    ['style' => 'color: #008000; font-weight: bold;']
                                )
                            );
                            echo '</tr>';

                            break;
                    }
                }
            }
            if ($debug) error_log(' ------ ');
        } // end for that loops over all answers of the current question

        if ($debug) error_log('-- end answer loop --');

        $final_answer = true;

        foreach ($real_answers as $my_answer) {
            if (!$my_answer) {
                $final_answer = false;
            }
        }

        //we add the total score after dealing with the answers
        if ($answerType == MULTIPLE_ANSWER_COMBINATION ||
            $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE
        ) {
            if ($final_answer) {
                //getting only the first score where we save the weight of all the question
                $answerWeighting = $objAnswerTmp->selectWeighting(1);
                $questionScore += $answerWeighting;
                $totalScore += $answerWeighting;
            }
        }

        //Fixes multiple answer question in order to be exact
        //if ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
       /* if ($answerType == GLOBAL_MULTIPLE_ANSWER) {
            $diff = @array_diff($answer_correct_array, $real_answers);

            // All good answers or nothing works like exact

            $counter = 1;
            $correct_answer = true;
            foreach ($real_answers as $my_answer) {
                if ($debug)
                    error_log(" my_answer: $my_answer answer_correct_array[counter]: ".$answer_correct_array[$counter]);
                if ($my_answer != $answer_correct_array[$counter]) {
                    $correct_answer = false;
                    break;
                }
                $counter++;
            }

            if ($debug) error_log(" answer_correct_array: ".print_r($answer_correct_array, 1)."");
            if ($debug) error_log(" real_answers: ".print_r($real_answers, 1)."");
            if ($debug) error_log(" correct_answer: ".$correct_answer);

            if ($correct_answer == false) {
                $questionScore = 0;
            }

            // This makes the result non exact
            if (!empty($diff)) {
                $questionScore = 0;
            }
        }*/

        $extra_data = array(
            'final_overlap' => $final_overlap,
            'final_missing'=>$final_missing,
            'final_excess'=> $final_excess,
            'overlap_color' => $overlap_color,
            'missing_color'=>$missing_color,
            'excess_color'=> $excess_color,
            'threadhold1'   => $threadhold1,
            'threadhold2'=>$threadhold2,
            'threadhold3'=> $threadhold3,
        );
        if ($from == 'exercise_result') {
            // if answer is hotspot. To the difference of exercise_show.php,
            //  we use the results from the session (from_db=0)
            // TODO Change this, because it is wrong to show the user
            //  some results that haven't been stored in the database yet
            if ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER || $answerType == HOT_SPOT_DELINEATION ) {

                if ($debug) error_log('$from AND this is a hotspot kind of question ');

                $my_exe_id = 0;
                $from_database = 0;
                if ($answerType == HOT_SPOT_DELINEATION) {
                    if (0) {
                        if ($overlap_color) {
                            $overlap_color='green';
                        } else {
                            $overlap_color='red';
                        }
                        if ($missing_color) {
                            $missing_color='green';
                        } else {
                            $missing_color='red';
                        }
                        if ($excess_color) {
                            $excess_color='green';
                        } else {
                            $excess_color='red';
                        }
                        if (!is_numeric($final_overlap)) {
                            $final_overlap = 0;
                        }
                        if (!is_numeric($final_missing)) {
                            $final_missing = 0;
                        }
                        if (!is_numeric($final_excess)) {
                            $final_excess = 0;
                        }

                        if ($final_overlap>100) {
                            $final_overlap = 100;
                        }

                        $table_resume='<table class="data_table">
                                <tr class="row_odd" >
                                    <td></td>
                                    <td ><b>' . get_lang('Requirements') . '</b></td>
                                    <td><b>' . get_lang('YourAnswer') . '</b></td>
                                </tr>
                                <tr class="row_even">
                                    <td><b>' . get_lang('Overlap') . '</b></td>
                                    <td>' . get_lang('Min') . ' ' . $threadhold1 . '</td>
                                    <td><div style="color:' . $overlap_color . '">'
                                        . (($final_overlap < 0) ? 0 : intval($final_overlap)) . '</div></td>
                                </tr>
                                <tr>
                                    <td><b>' . get_lang('Excess') . '</b></td>
                                    <td>' . get_lang('Max') . ' ' . $threadhold2 . '</td>
                                    <td><div style="color:' . $excess_color . '">'
                                        . (($final_excess < 0) ? 0 : intval($final_excess)) . '</div></td>
                                </tr>
                                <tr class="row_even">
                                    <td><b>' . get_lang('Missing') . '</b></td>
                                    <td>' . get_lang('Max') . ' ' . $threadhold3 . '</td>
                                    <td><div style="color:' . $missing_color . '">'
                                        . (($final_missing < 0) ? 0 : intval($final_missing)) . '</div></td>
                                </tr>
                            </table>';
                        if ($next == 0) {
                            $try = $try_hotspot;
                            $lp = $lp_hotspot;
                            $destinationid = $select_question_hotspot;
                            $url = $url_hotspot;
                        } else {
                            //show if no error
                            //echo 'no error';
                            $comment = $answerComment = $objAnswerTmp->selectComment($nbrAnswers);
                            $answerDestination = $objAnswerTmp->selectDestination($nbrAnswers);
                        }

                        echo '<h1><div style="color:#333;">' . get_lang('Feedback') . '</div></h1>
                            <p style="text-align:center">';

                        $message = '<p>' . get_lang('YourDelineation') . '</p>';
                        $message .= $table_resume;
                        $message .= '<br />' . get_lang('ResultIs') . ' ' . $result_comment . '<br />';
                        if ($organs_at_risk_hit > 0) {
                            $message .= '<p><b>' . get_lang('OARHit') . '</b></p>';
                        }
                        $message .='<p>' . $comment . '</p>';
                        echo $message;
                    } else {
                        echo $hotspot_delineation_result[0]; //prints message
                        $from_database = 1;  // the hotspot_solution.swf needs this variable
                    }

                    //save the score attempts

                    if (1) {
                        //getting the answer 1 or 0 comes from exercise_submit_modal.php
                        $final_answer = $hotspot_delineation_result[1];
                        if ($final_answer == 0) {
                            $questionScore = 0;
                        }
                        // we always insert the answer_id 1 = delineation
                        Event::saveQuestionAttempt($questionScore, 1, $quesId, $exeId, 0);
                        //in delineation mode, get the answer from $hotspot_delineation_result[1]
                        Event::saveExerciseAttemptHotspot(
                            $exeId,
                            $quesId,
                            1,
                            $hotspot_delineation_result[1],
                            $exerciseResultCoordinates[$quesId]
                        );
                    } else {
                        if ($final_answer==0) {
                            $questionScore = 0;
                            $answer=0;
                            Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0);
                            if (is_array($exerciseResultCoordinates[$quesId])) {
                                foreach($exerciseResultCoordinates[$quesId] as $idx => $val) {
                                    Event::saveExerciseAttemptHotspot($exeId,$quesId,$idx,0,$val);
                                }
                            }
                        } else {
                            Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0);
                            if (is_array($exerciseResultCoordinates[$quesId])) {
                                foreach($exerciseResultCoordinates[$quesId] as $idx => $val) {
                                    Event::saveExerciseAttemptHotspot($exeId,$quesId,$idx,$choice[$idx],$val);
                                }
                            }
                        }
                    }
                    $my_exe_id = $exeId;
                }
            }

            if ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER) {
                // We made an extra table for the answers

                if ($show_result) {
                    $relPath = api_get_path(REL_PATH);
                    //	if ($origin != 'learnpath') {
                    echo '</table></td></tr>';
                    echo "
                        <tr>
                            <td colspan=\"2\">
                                <p><em>" . get_lang('HotSpot') . "</em></p>

                                <div id=\"hotspot-solution-$questionId\"></div>

                                <script>
                                    $(document).on('ready', function () {
                                        new HotspotQuestion({
                                            questionId: $questionId,
                                            exerciseId: $exeId,
                                            selector: '#hotspot-solution-$questionId',
                                            for: 'solution',
                                            relPath: '$relPath'
                                        });
                                    });

                                </script>
                            </td>
                        </tr>
                    ";
                    //	}
                }
            }

            //if ($origin != 'learnpath') {
            if ($show_result) {
                echo '</table>';
            }
            //	}
        }
        unset ($objAnswerTmp);

        $totalWeighting += $questionWeighting;
        // Store results directly in the database
        // For all in one page exercises, the results will be
        // stored by exercise_results.php (using the session)

        if ($saved_results) {
            if ($debug) error_log("Save question results $saved_results");
            if ($debug) error_log(print_r($choice ,1 ));

            if (empty($choice)) {
                $choice = 0;
            }
            if ($answerType == MULTIPLE_ANSWER_TRUE_FALSE || $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
                if ($choice != 0) {
                    $reply = array_keys($choice);
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        Event::saveQuestionAttempt(
                            $questionScore,
                            $ans . ':' . $choice[$ans],
                            $quesId,
                            $exeId,
                            $i,
                            $this->id
                        );
                        if ($debug) {
                            error_log('result =>' . $questionScore . ' ' . $ans . ':' . $choice[$ans]);
                        }
                    }
                } else {
                    Event::saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id);
                }
            } elseif ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
                if ($choice != 0) {
                    $reply = array_keys($choice);

                    if ($debug) {
                        error_log("reply " . print_r($reply, 1) . "");
                    }
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        Event::saveQuestionAttempt($questionScore, $ans, $quesId, $exeId, $i, $this->id);
                    }
                } else {
                    Event::saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id);
                }
            } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION) {
                if ($choice != 0) {
                    $reply = array_keys($choice);
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        Event::saveQuestionAttempt($questionScore, $ans, $quesId, $exeId, $i, $this->id);
                    }
                } else {
                    Event::saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id);
                }
            } elseif (in_array($answerType, [MATCHING, DRAGGABLE, MATCHING_DRAGGABLE])) {
                if (isset($matching)) {
                    foreach ($matching as $j => $val) {
                        Event::saveQuestionAttempt($questionScore, $val, $quesId, $exeId, $j, $this->id);
                    }
                }
            } elseif ($answerType == FREE_ANSWER) {
                $answer = $choice;
                Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id);
            } elseif ($answerType == ORAL_EXPRESSION) {
                $answer = $choice;
                Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id, $nano);
            } elseif (in_array($answerType, [UNIQUE_ANSWER, UNIQUE_ANSWER_IMAGE, UNIQUE_ANSWER_NO_OPTION])) {
                $answer = $choice;
                Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id);
                //            } elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_DELINEATION) {
            } elseif ($answerType == HOT_SPOT) {
                $answer = [];

                if (isset($exerciseResultCoordinates[$questionId]) && !empty($exerciseResultCoordinates[$questionId])) {
                    Database::delete(
                        Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT),
                        [
                            'hotspot_exe_id = ? AND hotspot_question_id = ? AND c_id = ?' => [
                                $exeId,
                                $questionId,
                                api_get_course_int_id()
                            ]
                        ]
                    );

                    foreach ($exerciseResultCoordinates[$questionId] as $idx => $val) {
                        $answer[] = $val;

                        Event::saveExerciseAttemptHotspot($exeId, $quesId, $idx, $choice[$idx], $val, false, $this->id);
                    }
                }

                Event::saveQuestionAttempt($questionScore, implode('|', $answer), $quesId, $exeId, 0, $this->id);
            } else {
                Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0,$this->id);
            }
        }

        if ($propagate_neg == 0 && $questionScore < 0) {
            $questionScore = 0;
        }

        if ($saved_results) {
            $stat_table = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sql = 'UPDATE ' . $stat_table . ' SET
                        exe_result = exe_result + ' . floatval($questionScore) . '
                    WHERE exe_id = ' . $exeId;
            if ($debug) error_log($sql);
            Database::query($sql);
        }

        $return_array = array(
            'score'         => $questionScore,
            'weight'        => $questionWeighting,
            'extra'         => $extra_data,
            'open_question' => $arrques,
            'open_answer'   => $arrans,
            'answer_type'   => $answerType
        );

        return $return_array;
    }

    /**
     * Sends a notification when a user ends an examn
     *
     */
    public function send_mail_notification_for_exam($question_list_answers, $origin, $exe_id)
    {
        if (api_get_course_setting('email_alert_manager_on_new_quiz') != 1 ) {
            return null;
        }
        // Email configuration settings
        $courseCode = api_get_course_id();
        $courseInfo = api_get_course_info($courseCode);
        $sessionId = api_get_session_id();

        if (empty($courseInfo)) {
            return false;
        }

        $url_email = api_get_path(WEB_CODE_PATH)
            . 'exercice/exercise_show.php?'
            . api_get_cidreq()
            . '&id_session='
            . $sessionId
            . '&id='
            . $exe_id
            . '&action=qualify';
        $user_info = api_get_user_info(api_get_user_id());

        $msg = get_lang('ExerciseAttempted').'<br /><br />'
                    .get_lang('AttemptDetails').' : <br /><br />'.
                    '<table>'
                        .'<tr>'
                            .'<td><em>'.get_lang('CourseName').'</em></td>'
                            .'<td>&nbsp;<b>#course#</b></td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('TestAttempted').'</td>'
                            .'<td>&nbsp;#exercise#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentName').'</td>'
                            .'<td>&nbsp;#firstName# #lastName#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentEmail').'</td>'
                            .'<td>&nbsp;#email#</td>'
                        .'</tr>'
                    .'</table>';
        $open_question_list = null;

        $msg = str_replace("#email#", $user_info['email'], $msg);
        $msg1 = str_replace("#exercise#", $this->exercise, $msg);
        $msg = str_replace("#firstName#", $user_info['firstname'], $msg1);
        $msg1 = str_replace("#lastName#", $user_info['lastname'], $msg);
        $msg = str_replace("#course#", $courseInfo['name'], $msg1);

        if ($origin != 'learnpath') {
            $msg.= '<br /><a href="#url#">'.get_lang('ClickToCommentAndGiveFeedback').'</a>';
        }
        $msg1 = str_replace("#url#", $url_email, $msg);
        $mail_content = $msg1;
        $subject = get_lang('ExerciseAttempted');

        if (!empty($sessionId)) {
            $teachers = CourseManager::get_coach_list_from_course_code($courseCode, $sessionId);
        } else {
            $teachers = CourseManager::get_teacher_list_from_course_code($courseCode);
        }

        if (!empty($teachers)) {
            foreach ($teachers as $user_id => $teacher_data) {
                MessageManager::send_message_simple(
                    $user_id,
                    $subject,
                    $mail_content
                );
            }
        }
    }

    /**
     * Sends a notification when a user ends an examn
     *
     */
    function send_notification_for_open_questions($question_list_answers, $origin, $exe_id)
    {
        if (api_get_course_setting('email_alert_manager_on_new_quiz') != 1 ) {
            return null;
        }
        // Email configuration settings
        $courseCode     = api_get_course_id();
        $course_info    = api_get_course_info($courseCode);

        $url_email = api_get_path(WEB_CODE_PATH)
            . 'exercice/exercise_show.php?'
            . api_get_cidreq()
            . '&id_session='
            . api_get_session_id()
            . '&id='
            . $exe_id
            . '&action=qualify';
        $user_info = api_get_user_info(api_get_user_id());

        $msg = get_lang('OpenQuestionsAttempted').'<br /><br />'
                    .get_lang('AttemptDetails').' : <br /><br />'
                    .'<table>'
                        .'<tr>'
                            .'<td><em>'.get_lang('CourseName').'</em></td>'
                            .'<td>&nbsp;<b>#course#</b></td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('TestAttempted').'</td>'
                            .'<td>&nbsp;#exercise#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentName').'</td>'
                            .'<td>&nbsp;#firstName# #lastName#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentEmail').'</td>'
                            .'<td>&nbsp;#mail#</td>'
                        .'</tr>'
                    .'</table>';
        $open_question_list = null;
        foreach ($question_list_answers as $item) {
            $question    = $item['question'];
            $answer      = $item['answer'];
            $answer_type = $item['answer_type'];

            if (!empty($question) && !empty($answer) && $answer_type == FREE_ANSWER) {
                $open_question_list .=
                    '<tr>'
                        .'<td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Question').'</td>'
                        .'<td width="473" valign="top" bgcolor="#F3F3F3">'.$question.'</td>'
                    .'</tr>'
                    .'<tr>'
                        .'<td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Answer').'</td>'
                        .'<td valign="top" bgcolor="#F3F3F3">'.$answer.'</td>'
                    .'</tr>';
            }
        }

        if (!empty($open_question_list)) {
            $msg .= '<p><br />'.get_lang('OpenQuestionsAttemptedAre').' :</p>'.
                    '<table width="730" height="136" border="0" cellpadding="3" cellspacing="3">';
            $msg .= $open_question_list;
            $msg .= '</table><br />';


            $msg1   = str_replace("#exercise#",    $this->exercise, $msg);
            $msg    = str_replace("#firstName#",   $user_info['firstname'],$msg1);
            $msg1   = str_replace("#lastName#",    $user_info['lastname'],$msg);
            $msg    = str_replace("#mail#",        $user_info['email'],$msg1);
            $msg    = str_replace("#course#",      $course_info['name'],$msg1);

            if ($origin != 'learnpath') {
                $msg .= '<br /><a href="#url#">'.get_lang('ClickToCommentAndGiveFeedback').'</a>';
            }
            $msg1 = str_replace("#url#", $url_email, $msg);
            $mail_content = $msg1;
            $subject = get_lang('OpenQuestionsAttempted');

            if (api_get_session_id()) {
                $teachers = CourseManager::get_coach_list_from_course_code($courseCode, api_get_session_id());
            } else {
                $teachers = CourseManager::get_teacher_list_from_course_code($courseCode);
            }

            if (!empty($teachers)) {
                foreach ($teachers as $user_id => $teacher_data) {
                    MessageManager::send_message_simple(
                        $user_id,
                        $subject,
                        $mail_content
                    );
                }
            }
        }
    }

    function send_notification_for_oral_questions($question_list_answers, $origin, $exe_id)
    {
        if (api_get_course_setting('email_alert_manager_on_new_quiz') != 1 ) {
            return null;
        }
        // Email configuration settings
        $courseCode     = api_get_course_id();
        $course_info    = api_get_course_info($courseCode);

        $url_email = api_get_path(WEB_CODE_PATH)
            . 'exercice/exercise_show.php?'
            . api_get_cidreq()
            . '&id_session='
            . api_get_session_id()
            . '&id='
            . $exe_id
            . '&action=qualify';
        $user_info = api_get_user_info(api_get_user_id());

        $oral_question_list = null;
        foreach ($question_list_answers as $item) {
            $question    = $item['question'];
            $answer      = $item['answer'];
            $answer_type = $item['answer_type'];

            if (!empty($question) && !empty($answer) && $answer_type == ORAL_EXPRESSION) {
                $oral_question_list.='<br /><table width="730" height="136" border="0" cellpadding="3" cellspacing="3">'
                    .'<tr>'
                        .'<td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Question').'</td>'
                        .'<td width="473" valign="top" bgcolor="#F3F3F3">'.$question.'</td>'
                    .'</tr>'
                    .'<tr>'
                        .'<td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Answer').'</td>'
                        .'<td valign="top" bgcolor="#F3F3F3">'.$answer.'</td>'
                    .'</tr></table>';
            }
        }

        if (!empty($oral_question_list)) {
            $msg = get_lang('OralQuestionsAttempted').'<br /><br />
                    '.get_lang('AttemptDetails').' : <br /><br />'
                    .'<table>'
                        .'<tr>'
                            .'<td><em>'.get_lang('CourseName').'</em></td>'
                            .'<td>&nbsp;<b>#course#</b></td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('TestAttempted').'</td>'
                            .'<td>&nbsp;#exercise#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentName').'</td>'
                            .'<td>&nbsp;#firstName# #lastName#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentEmail').'</td>'
                            .'<td>&nbsp;#mail#</td>'
                        .'</tr>'
                    .'</table>';
            $msg .=  '<br />'.sprintf(get_lang('OralQuestionsAttemptedAreX'),$oral_question_list).'<br />';
            $msg1 = str_replace("#exercise#", $this->exercise, $msg);
            $msg = str_replace("#firstName#", $user_info['firstname'], $msg1);
            $msg1 = str_replace("#lastName#", $user_info['lastname'], $msg);
            $msg = str_replace("#mail#", $user_info['email'], $msg1);
            $msg = str_replace("#course#", $course_info['name'], $msg1);

            if ($origin != 'learnpath') {
                $msg.= '<br /><a href="#url#">'.get_lang('ClickToCommentAndGiveFeedback').'</a>';
            }
            $msg1 = str_replace("#url#", $url_email, $msg);
            $mail_content = $msg1;
            $subject = get_lang('OralQuestionsAttempted');

            if (api_get_session_id()) {
                $teachers = CourseManager::get_coach_list_from_course_code($courseCode, api_get_session_id());
            } else {
                $teachers = CourseManager::get_teacher_list_from_course_code($courseCode);
            }

            if (!empty($teachers)) {
                foreach ($teachers as $user_id => $teacher_data) {
                    MessageManager::send_message_simple(
                        $user_id,
                        $subject,
                        $mail_content
                    );
                }
            }
        }
    }

    /**
     * @param array $user_data result of api_get_user_info()
     * @param null $start_date
     * @param null $duration
     * @param string $ip Optional. The user IP
     * @return string
     */
    public function show_exercise_result_header($user_data, $start_date = null, $duration = null, $ip = null)
    {
        $array = array();

        if (!empty($user_data)) {
            $array[] = array('title' => get_lang('Name'), 'content' => $user_data['complete_name']);
            $array[] = array('title' => get_lang('Username'), 'content' => $user_data['username']);
            if (!empty($user_data['official_code'])) {
                $array[] = array(
                    'title' => get_lang('OfficialCode'),
                    'content' => $user_data['official_code']
                );
            }
        }
        // Description can be very long and is generally meant to explain
        //   rules *before* the exam. Leaving here to make display easier if
        //   necessary
        /*
        if (!empty($this->description)) {
            $array[] = array('title' => get_lang("Description"), 'content' => $this->description);
        }
        */
        if (!empty($start_date)) {
            $array[] = array('title' => get_lang('StartDate'), 'content' => $start_date);
        }

        if (!empty($duration)) {
            $array[] = array('title' => get_lang('Duration'), 'content' => $duration);
        }

        if (!empty($ip)) {
            $array[] = array('title' => get_lang('IP'), 'content' => $ip);
        }
        $html  = '<div class="question-result">';
        $html .= Display::page_header(
            Display::return_icon('test-quiz.png', get_lang('Result'),null, ICON_SIZE_MEDIUM).' '.$this->exercise.' : '.get_lang('Result')
        );
        $html .= Display::description($array);
        $html .="</div>";
        return $html;
    }

    /**
     * Create a quiz from quiz data
     * @param string  Title
     * @param int     Time before it expires (in minutes)
     * @param int     Type of exercise
     * @param int     Whether it's randomly picked questions (1) or not (0)
     * @param int     Whether the exercise is visible to the user (1) or not (0)
     * @param int     Whether the results are show to the user (0) or not (1)
     * @param int     Maximum number of attempts (0 if no limit)
     * @param int     Feedback type
     * @todo this was function was added due the import exercise via CSV
     * @return    int New exercise ID
     */
    public function createExercise(
        $title,
        $expired_time = 0,
        $type = 2,
        $random = 0,
        $active = 1,
        $results_disabled = 0,
        $max_attempt = 0,
        $feedback = 3,
        $propagateNegative = 0
    ) {
        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $type = intval($type);
        $random = intval($random);
        $active = intval($active);
        $results_disabled = intval($results_disabled);
        $max_attempt = intval($max_attempt);
        $feedback = intval($feedback);
        $expired_time = intval($expired_time);
        $title = Database::escape_string($title);
        $propagateNegative = intval($propagateNegative);
        $sessionId = api_get_session_id();
        $course_id = api_get_course_int_id();
        // Save a new quiz
        $sql = "INSERT INTO $tbl_quiz (
                c_id,
                title,
                type,
                random,
                active,
                results_disabled,
                max_attempt,
                start_time,
                end_time,
                feedback_type,
                expired_time,
                session_id,
                propagate_neg
            )
            VALUES (
                '$course_id',
                '$title',
                $type,
                $random,
                $active,
                $results_disabled,
                $max_attempt,
                '',
                '',
                $feedback,
                $expired_time,
                $sessionId,
                $propagateNegative
            )";
        Database::query($sql);
        $quiz_id = Database::insert_id();

        if ($quiz_id) {

            $sql = "UPDATE $tbl_quiz SET id = iid WHERE iid = {$quiz_id} ";
            Database::query($sql);
        }

        return $quiz_id;
    }

    function process_geometry()
    {

    }

    /**
     * Returns the exercise result
     * @param 	int		attempt id
     * @return 	float 	exercise result
     */
    public function get_exercise_result($exe_id)
    {
        $result = array();
        $track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($exe_id);

        if (!empty($track_exercise_info)) {
            $totalScore = 0;
            $objExercise = new Exercise();
            $objExercise->read($track_exercise_info['exe_exo_id']);
            if (!empty($track_exercise_info['data_tracking'])) {
                $question_list = explode(',', $track_exercise_info['data_tracking']);
            }
            foreach ($question_list as $questionId) {
                $question_result = $objExercise->manage_answer(
                    $exe_id,
                    $questionId,
                    '',
                    'exercise_show',
                    array(),
                    false,
                    true,
                    false,
                    $objExercise->selectPropagateNeg()
                );
                $totalScore      += $question_result['score'];
            }

            if ($objExercise->selectPropagateNeg() == 0 && $totalScore < 0) {
                $totalScore = 0;
            }
            $result = array(
                'score' => $totalScore,
                'weight' => $track_exercise_info['exe_weighting']
            );
        }
        return $result;
    }

    /**
     *  Checks if the exercise is visible due a lot of conditions - visibility, time limits, student attempts
     * @return bool true if is active
     */
    public function is_visible(
        $lp_id = 0,
        $lp_item_id = 0,
        $lp_item_view_id = 0,
        $filter_by_admin = true
    ) {
        // 1. By default the exercise is visible
        $is_visible = true;
        $message = null;

        // 1.1 Admins and teachers can access to the exercise
        if ($filter_by_admin) {
            if (api_is_platform_admin() || api_is_course_admin()) {
                return array('value' => true, 'message' => '');
            }
        }

        // Deleted exercise.
        if ($this->active == -1) {
            return array(
                'value' => false,
                'message' => Display::return_message(get_lang('ExerciseNotFound'), 'warning', false)
            );
        }

        // Checking visibility in the item_property table.
        $visibility = api_get_item_visibility(
            api_get_course_info(),
            TOOL_QUIZ,
            $this->id,
            api_get_session_id()
        );

        if ($visibility == 0 || $visibility == 2) {
            $this->active = 0;
        }

        // 2. If the exercise is not active.
        if (empty($lp_id)) {
            // 2.1 LP is OFF
            if ($this->active == 0) {
                return array(
                    'value' => false,
                    'message' => Display::return_message(get_lang('ExerciseNotFound'), 'warning', false)
                );
            }
        } else {
            // 2.1 LP is loaded
            if ($this->active == 0 && !learnpath::is_lp_visible_for_student($lp_id, api_get_user_id())) {
                return array(
                    'value' => false,
                    'message' => Display::return_message(get_lang('ExerciseNotFound'), 'warning', false)
                );
            }
        }

        //3. We check if the time limits are on
        $limit_time_exists = (
            (!empty($this->start_time) && $this->start_time != '0000-00-00 00:00:00') ||
            (!empty($this->end_time) && $this->end_time != '0000-00-00 00:00:00')
        ) ? true : false;


        if ($limit_time_exists) {
            $time_now = time();

            if (!empty($this->start_time) && $this->start_time != '0000-00-00 00:00:00') {
                $is_visible = (($time_now - api_strtotime($this->start_time, 'UTC')) > 0) ? true : false;
            }

            if ($is_visible == false) {
                $message = sprintf(
                    get_lang('ExerciseAvailableFromX'),
                    api_convert_and_format_date($this->start_time)
                );
            }

            if ($is_visible == true) {
                if ($this->end_time != '0000-00-00 00:00:00') {
                    $is_visible = ((api_strtotime($this->end_time, 'UTC') > $time_now) > 0) ? true : false;
                    if ($is_visible == false) {
                        $message = sprintf(
                            get_lang('ExerciseAvailableUntilX'),
                            api_convert_and_format_date($this->end_time)
                        );
                    }
                }
            }
            if (
                $is_visible == false &&
                $this->start_time != '0000-00-00 00:00:00' &&
                $this->end_time != '0000-00-00 00:00:00'
            ) {
                $message = sprintf(
                    get_lang('ExerciseWillBeActivatedFromXToY'),
                    api_convert_and_format_date($this->start_time),
                    api_convert_and_format_date($this->end_time)
                );
            }
        }

        // 4. We check if the student have attempts
        $exerciseAttempts = $this->selectAttempts();

        if ($is_visible) {
            if ($exerciseAttempts > 0) {

                $attempt_count = Event::get_attempt_count_not_finished(
                    api_get_user_id(),
                    $this->id,
                    $lp_id,
                    $lp_item_id,
                    $lp_item_view_id
                );

                if ($attempt_count >= $exerciseAttempts) {
                    $message = sprintf(
                        get_lang('ReachedMaxAttempts'),
                        $this->name,
                        $exerciseAttempts
                    );
                    $is_visible = false;
                }
            }
        }

        if (!empty($message)){
            $message = Display::return_message($message, 'warning', false);
        }

        return array(
            'value' => $is_visible,
            'message' => $message
        );
    }

    public function added_in_lp()
    {
        $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT max_score FROM $TBL_LP_ITEM
            WHERE c_id = {$this->course_id} AND item_type = '" . TOOL_QUIZ . "' AND path = '{$this->id}'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return true;
        }
        return false;
    }

    function get_media_list()
    {
        $media_questions = array();
        $question_list = $this->get_validated_question_list();
        if (!empty($question_list)) {
            foreach ($question_list as $questionId) {
                $objQuestionTmp = Question::read($questionId);
                if (isset($objQuestionTmp->parent_id) && $objQuestionTmp->parent_id != 0) {
                    $media_questions[$objQuestionTmp->parent_id][] = $objQuestionTmp->id;
                } else {
                    //Always the last item
                    $media_questions[999][] = $objQuestionTmp->id;
                }
            }
        }
        return $media_questions;
    }

    function media_is_activated($media_list)
    {
        $active = false;
        if (isset($media_list) && !empty($media_list)) {
            $media_count = count($media_list);
            if ($media_count > 1) {
                return true;
            } elseif ($media_count == 1) {
                if (isset($media_list[999])) {
                    return false;
                } else {
                    return true;
                }
            }
        }
        return $active;
    }

    function get_validated_question_list()
    {
        $tabres = array();
        $isRandomByCategory = $this->isRandomByCat();
        if ($isRandomByCategory == 0) {
            if ($this->isRandom()) {
                $tabres = $this->selectRandomList();
            } else {
                $tabres = $this->selectQuestionList();
            }
        } else {
            if ($this->isRandom()) {
                // USE question categories
                // get questions by category for this exercise
                // we have to choice $objExercise->random question in each array values of $tabCategoryQuestions
                // key of $tabCategoryQuestions are the categopy id (0 for not in a category)
                // value is the array of question id of this category
                $questionList = array();
                $tabCategoryQuestions = TestCategory::getQuestionsByCat($this->id);
                $isRandomByCategory = $this->selectRandomByCat();
                // on tri les categories en fonction du terme entre [] en tete de la description de la categorie
                /*
                 * ex de catégories :
                 * [biologie] Maitriser les mecanismes de base de la genetique
                 * [biologie] Relier les moyens de depenses et les agents infectieux
                 * [biologie] Savoir ou est produite l'enrgie dans les cellules et sous quelle forme
                 * [chimie] Classer les molles suivant leur pouvoir oxydant ou reacteur
                 * [chimie] Connaître la denition de la theoie acide/base selon Brönsted
                 * [chimie] Connaître les charges des particules
                 * On veut dans l'ordre des groupes definis par le terme entre crochet au debut du titre de la categorie
                */
                // If test option is Grouped By Categories
                if ($isRandomByCategory == 2) {
                    $tabCategoryQuestions = TestCategory::sortTabByBracketLabel($tabCategoryQuestions);
                }
                while (list($cat_id, $tabquestion) = each($tabCategoryQuestions)) {
                    $number_of_random_question = $this->random;
                    if ($this->random == -1) {
                        $number_of_random_question = count($this->questionList);
                    }
                    $questionList = array_merge(
                        $questionList,
                        TestCategory::getNElementsFromArray(
                            $tabquestion,
                            $number_of_random_question
                        )
                    );
                }
                // shuffle the question list if test is not grouped by categories
                if ($isRandomByCategory == 1) {
                    shuffle($questionList); // or not
                }
                $tabres = $questionList;
            } else {
                // Problem, random by category has been selected and
                // we have no $this->isRandom number of question selected
                // Should not happened
            }
        }
        return $tabres;
    }

    function get_question_list($expand_media_questions = false)
    {
        $question_list = $this->get_validated_question_list();
        $question_list = $this->transform_question_list_with_medias($question_list, $expand_media_questions);
        return $question_list;
    }

    function transform_question_list_with_medias($question_list, $expand_media_questions = false)
    {
        $new_question_list = array();
        if (!empty($question_list)) {
            $media_questions = $this->get_media_list();
            $media_active = $this->media_is_activated($media_questions);

            if ($media_active) {
                $counter = 1;
                foreach ($question_list as $question_id) {
                    $add_question = true;
                    foreach ($media_questions as $media_id => $question_list_in_media) {
                        if ($media_id != 999 && in_array($question_id, $question_list_in_media)) {
                            $add_question = false;
                            if (!in_array($media_id, $new_question_list)) {
                                $new_question_list[$counter] = $media_id;
                                $counter++;
                            }
                            break;
                        }
                    }
                    if ($add_question) {
                        $new_question_list[$counter] = $question_id;
                        $counter++;
                    }
                }
                if ($expand_media_questions) {
                    $media_key_list = array_keys($media_questions);
                    foreach ($new_question_list as &$question_id) {
                        if (in_array($question_id, $media_key_list)) {
                            $question_id = $media_questions[$question_id];
                        }
                    }
                    $new_question_list = array_flatten($new_question_list);
                }
            } else {
                $new_question_list = $question_list;
            }
        }
        return $new_question_list;
    }

    /**
     * @param int $exe_id
     * @return array|mixed
     */
    public function get_stat_track_exercise_info_by_exe_id($exe_id)
    {
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $exe_id = intval($exe_id);
        $sql_track = "SELECT * FROM $track_exercises WHERE exe_id = $exe_id ";
        $result = Database::query($sql_track);
        $new_array = array();
        if (Database::num_rows($result) > 0 ) {
            $new_array = Database::fetch_array($result, 'ASSOC');

            $new_array['duration'] = null;

            $start_date = api_get_utc_datetime($new_array['start_date'], true);
            $end_date = api_get_utc_datetime($new_array['exe_date'], true);

            if (!empty($start_date) && !empty($end_date)) {
                $start_date = api_strtotime($start_date, 'UTC');
                $end_date = api_strtotime($end_date, 'UTC');
                if ($start_date && $end_date) {
                    $mytime = $end_date- $start_date;
                    $new_learnpath_item = new learnpathItem(null);
                    $time_attemp = $new_learnpath_item->get_scorm_time('js', $mytime);
                    $h = get_lang('h');
                    $time_attemp = str_replace('NaN', '00' . $h . '00\'00"', $time_attemp);
                    $new_array['duration'] = $time_attemp;
                }
            }
        }
        return $new_array;
    }

    public function edit_question_to_remind($exe_id, $question_id, $action = 'add')
    {
        $exercise_info = self::get_stat_track_exercise_info_by_exe_id($exe_id);
        $question_id = intval($question_id);
        $exe_id = intval($exe_id);
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        if ($exercise_info) {

            if (empty($exercise_info['questions_to_check'])) {
                if ($action == 'add') {
                    $sql = "UPDATE $track_exercises SET questions_to_check = '$question_id' WHERE exe_id = $exe_id ";
                    $result = Database::query($sql);
                }
            } else {
                $remind_list = explode(',',$exercise_info['questions_to_check']);

                $remind_list_string = '';
                if ($action == 'add') {
                    if (!in_array($question_id, $remind_list)) {
                        $remind_list[] = $question_id;
                        if (!empty($remind_list)) {
                            sort($remind_list);
                            array_filter($remind_list);
                        }
                        $remind_list_string = implode(',', $remind_list);
                    }
                } elseif ($action == 'delete')  {
                    if (!empty($remind_list)) {
                        if (in_array($question_id, $remind_list)) {
                            $remind_list = array_flip($remind_list);
                            unset($remind_list[$question_id]);
                            $remind_list = array_flip($remind_list);

                            if (!empty($remind_list)) {
                                sort($remind_list);
                                array_filter($remind_list);
                                $remind_list_string = implode(',', $remind_list);
                            }
                        }
                    }
                }
                $remind_list_string = Database::escape_string($remind_list_string);
                $sql = "UPDATE $track_exercises SET questions_to_check = '$remind_list_string' WHERE exe_id = $exe_id ";
                Database::query($sql);
            }
        }
    }

    public function fill_in_blank_answer_to_array($answer)
    {
        api_preg_match_all('/\[[^]]+\]/', $answer, $teacher_answer_list);
        $teacher_answer_list = $teacher_answer_list[0];
        return $teacher_answer_list;
    }

    public function fill_in_blank_answer_to_string($answer)
    {
        $teacher_answer_list = $this->fill_in_blank_answer_to_array($answer);
        $result = '';
        if (!empty($teacher_answer_list)) {
            $i = 0;
            foreach ($teacher_answer_list as $teacher_item) {
                $value = null;
                //Cleaning student answer list
                $value = strip_tags($teacher_item);
                $value = api_substr($value, 1, api_strlen($value) - 2);
                $value = explode('/', $value);
                if (!empty($value[0])) {
                    $value = trim($value[0]);
                    $value = str_replace('&nbsp;', '', $value);
                    $result .= $value;
                }
            }
        }
        return $result;
    }

    function return_time_left_div()
    {
        $html = '<div id="clock_warning" style="display:none">';
        $html .= Display::return_message(
            get_lang('ReachedTimeLimit'),
            'warning'
        );
        $html .= ' ';
        $html .= sprintf(
            get_lang('YouWillBeRedirectedInXSeconds'),
            '<span id="counter_to_redirect" class="red_alert"></span>'
        );
        $html .= '</div>';
        $html .= '<div id="exercise_clock_warning" class="well count_down"></div>';
        return $html;
    }

    function get_count_question_list()
    {
        //Real question count
        $question_count = 0;
        $question_list = $this->get_question_list();
        if (!empty($question_list)) {
            $question_count = count($question_list);
        }
        return $question_count;
    }

    function get_exercise_list_ordered()
    {
        $table_exercise_order = Database::get_course_table(TABLE_QUIZ_ORDER);
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $sql = "SELECT exercise_id, exercise_order FROM $table_exercise_order WHERE c_id = $course_id AND session_id = $session_id ORDER BY exercise_order";
        $result = Database::query($sql);
        $list = array();
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $list[$row['exercise_order']] = $row['exercise_id'];
            }
        }
        return $list;
    }

    /**
     * Calculate the max_score of the quiz, depending of question inside, and quiz advanced option
     */
    public function get_max_score()
    {
        $out_max_score = 0;
        $tab_question_list = $this->selectQuestionList(true);   // list of question's id !!! the array key start at 1 !!!
        // test is randomQuestions - see field random of test
        if ($this->random > 0 && $this->randomByCat == 0) {
            $nb_random_questions = $this->random;
            $tab_questions_score = array();
            for ($i = 1; $i <= count($tab_question_list); $i++) {
                $tmpobj_question = Question::read($tab_question_list[$i]);
                $tab_questions_score[] = $tmpobj_question->weighting;
            }
            rsort($tab_questions_score);
            // add the first $nb_random_questions value of score array to get max_score
            for ($i = 0; $i < min($nb_random_questions, count($tab_questions_score)); $i++) {
                $out_max_score += $tab_questions_score[$i];
            }
        }
        // test is random by category
        // get the $nb_random_questions best score question of each category
        else if ($this->random > 0 && $this->randomByCat > 0) {
            $nb_random_questions = $this->random;
            $tab_categories_scores = array();
            for ($i = 1; $i <= count($tab_question_list); $i++) {
                $question_category_id = TestCategory::getCategoryForQuestion($tab_question_list[$i]);
                if (!is_array($tab_categories_scores[$question_category_id])) {
                    $tab_categories_scores[$question_category_id] = array();
                }
                $tmpobj_question = Question::read($tab_question_list[$i]);
                $tab_categories_scores[$question_category_id][] = $tmpobj_question->weighting;
            }
            // here we've got an array with first key, the category_id, second key, score of question for this cat
            while (list($key, $tab_scores) = each($tab_categories_scores)) {
                rsort($tab_scores);
                for ($i = 0; $i < min($nb_random_questions, count($tab_scores)); $i++) {
                    $out_max_score += $tab_scores[$i];
                }
            }
        }
        // standart test, just add each question score
        else {
            for ($i = 1; $i <= count($tab_question_list); $i++) {
                $tmpobj_question = Question::read($tab_question_list[$i]);
                $out_max_score += $tmpobj_question->weighting;
            }
        }
        return $out_max_score;
    }

    /**
    * @return string
    */
    public function get_formated_title()
    {
        return api_html_entity_decode($this->selectTitle());
    }

    /**
     * @param $in_title
     * @return string
     */
    public static function get_formated_title_variable($in_title)
    {
        return api_html_entity_decode($in_title);
    }

    /**
     * @return string
     */
    public function format_title()
    {
        return api_htmlentities($this->title);
    }

    /**
     * @param $in_title
     * @return string
     */
    public static function format_title_variable($in_title)
    {
        return api_htmlentities($in_title);
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     * @return array exercises
     */
    public function getExercisesByCouseSession($courseId, $sessionId)
    {
        $courseId = intval($courseId);
        $sessionId = intval($sessionId);

        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "SELECT * FROM $tbl_quiz cq
                WHERE
                    cq.c_id = %s AND
                    (cq.session_id = %s OR cq.session_id = 0) AND
                    cq.active = 0
                ORDER BY cq.id";
        $sql = sprintf($sql, $courseId, $sessionId);

        $result = Database::query($sql);

        $rows = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     *
     * @param int $courseId
     * @param int $sessionId
     * @param array $quizId
     * @return array exercises
     */
    public function getExerciseAndResult($courseId, $sessionId, $quizId = array())
    {
        if (empty($quizId)) {
            return array();
        }

        $sessionId = intval($sessionId);

        $ids = is_array($quizId) ? $quizId : array($quizId);
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        if ($sessionId != 0) {
            $sql = "SELECT * FROM $track_exercises te
              INNER JOIN c_quiz cq ON cq.id = te.exe_exo_id AND te.c_id = cq.c_id
              WHERE
              te.id = %s AND
              te.session_id = %s AND
              cq.id IN (%s)
              ORDER BY cq.id";

            $sql = sprintf($sql, $courseId, $sessionId, $ids);
        } else {
            $sql = "SELECT * FROM $track_exercises te
              INNER JOIN c_quiz cq ON cq.id = te.exe_exo_id AND te.c_id = cq.c_id
              WHERE
              te.id = %s AND
              cq.id IN (%s)
              ORDER BY cq.id";
            $sql = sprintf($sql, $courseId, $ids);
        }
        $result = Database::query($sql);
        $rows = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $rows[] = $row;
        }

        return $rows;
    }
}
