<?php
/* For licensing terms, see /license.txt */

/**
 * Class Answer
 * Allows to instantiate an object of type Answer
 * 5 arrays are created to receive the attributes of each answer belonging to a specified question
 * @package chamilo.exercise
 *
 * @author Olivier Brouckaert
 */
class Answer
{
    public $questionId;

    // these are arrays
    public $answer;
    public $correct;
    public $comment;
    public $weighting;
    public $position;
    public $hotspot_coordinates;
    public $hotspot_type;
    public $destination;
    // these arrays are used to save temporarily new answers
    // then they are moved into the arrays above or deleted in the event of cancellation
    public $new_answer;
    public $new_correct;
    public $new_comment;
    public $new_weighting;
    public $new_position;
    public $new_hotspot_coordinates;
    public $new_hotspot_type;
    public $autoId;
    public $nbrAnswers;
    public $new_nbrAnswers;
    public $new_destination; // id of the next question if feedback option is set to Directfeedback
    public $course; //Course information
    public $iid;

    /**
     * constructor of the class
     *
     * @author 	Olivier Brouckaert
     * @param int $questionId that answers belong to
     * @param int $course_id
     */
    public function __construct($questionId, $course_id = null)
    {
        $this->questionId = intval($questionId);
        $this->answer = array();
        $this->correct = array();
        $this->comment = array();
        $this->weighting = array();
        $this->position = array();
        $this->hotspot_coordinates = array();
        $this->hotspot_type = array();
        $this->destination = array();
        // clears $new_* arrays
        $this->cancel();

        if (!empty($course_id)) {
            $courseInfo = api_get_course_info_by_id($course_id);
        } else {
            $courseInfo = api_get_course_info();
        }

        $this->course = $courseInfo;
        $this->course_id = $courseInfo['real_id'];

        // fills arrays
        $objExercise = new Exercise($this->course_id);
        $exerciseId = isset($_REQUEST['exerciseId']) ? $_REQUEST['exerciseId'] : null;
        $objExercise->read($exerciseId);

        if ($objExercise->random_answers == '1' && $this->getQuestionType() != CALCULATED_ANSWER) {
            $this->readOrderedBy('rand()', '');// randomize answers
        } else {
            $this->read(); // natural order
        }
    }

    /**
     * Clears $new_* arrays
     *
     * @author Olivier Brouckaert
     */
    public function cancel()
    {
        $this->new_answer = array();
        $this->new_correct = array();
        $this->new_comment = array();
        $this->new_weighting = array();
        $this->new_position = array();
        $this->new_hotspot_coordinates = array();
        $this->new_hotspot_type = array();
        $this->new_nbrAnswers = 0;
        $this->new_destination = array();
    }

    /**
     * Reads answer information from the database
     *
     * @author Olivier Brouckaert
     */
    public function read()
    {
        $TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $questionId = $this->questionId;

        $sql = "SELECT * FROM $TBL_ANSWER
                WHERE
                    c_id = {$this->course_id} AND
                    question_id ='".$questionId."'
                ORDER BY position";

        $result = Database::query($sql);
        $i=1;

        // while a record is found
        while ($object = Database::fetch_object($result)) {
            $this->id[$i] = $object->id;
            $this->answer[$i] = $object->answer;
            $this->correct[$i] = $object->correct;
            $this->comment[$i] = $object->comment;
            $this->weighting[$i] = $object->ponderation;
            $this->position[$i] = $object->position;
            $this->hotspot_coordinates[$i] = $object->hotspot_coordinates;
            $this->hotspot_type[$i] = $object->hotspot_type;
            $this->destination[$i] = $object->destination;
            $this->autoId[$i] = $object->id_auto;
            $this->iid[$i] = $object->iid;
            $i++;
        }
        $this->nbrAnswers = $i-1;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getAnswerByAutoId($id)
    {
        foreach ($this->autoId as $key => $autoId) {
            if ($autoId == $id) {
                $result =  [
                    'answer' => $this->answer[$key],
                    'correct' => $this->correct[$key],
                    'comment' => $this->comment[$key],
                ];

                return $result;
            }
        }

        return [];
    }

     /**
     * returns all answer ids from this question Id
     *
     * @author Yoselyn Castillo
     * @return array - $id (answer ids)
     */
    public function selectAnswerId()
    {
        $TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $questionId = $this->questionId;

        $sql="SELECT id FROM
              $TBL_ANSWER
              WHERE c_id = {$this->course_id} AND question_id ='".$questionId."'";

        $result = Database::query($sql);
        $id = array();
        // while a record is found
        if (Database::num_rows($result) > 0) {
            while ($object = Database::fetch_array($result)) {
                $id[] = $object['id'];
            }
        }

        return $id;
	}

    /**
     * Reads answer information from the data base ordered by parameter
     * @param	string	Field we want to order by
     * @param	string	DESC or ASC
     * @param string $field
     * @author 	Frederic Vauthier
     */
    public function readOrderedBy($field, $order='ASC')
    {
		$field = Database::escape_string($field);
		if (empty($field)) {
			$field = 'position';
		}

		if ($order != 'ASC' && $order!='DESC') {
			$order = 'ASC';
		}

		$TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
		$TBL_QUIZ = Database::get_course_table(TABLE_QUIZ_QUESTION);
		$questionId = intval($this->questionId);

		$sql = "SELECT type FROM $TBL_QUIZ
		        WHERE c_id = {$this->course_id} AND id = $questionId";
		$result_question = Database::query($sql);
		$questionType = Database::fetch_array($result_question);

        if ($questionType['type'] == DRAGGABLE) {
            // Random is done by submit.js.tpl
            $this->read();

            return true;
        }

		$sql = "SELECT
		            answer,
		            correct,
		            comment,
		            ponderation,
		            position,
		            hotspot_coordinates,
		            hotspot_type,
		            destination,
		            id_auto,
                    iid
                FROM $TBL_ANSWER
                WHERE
                    c_id = {$this->course_id} AND
                    question_id='".$questionId."'
                ORDER BY $field $order";
		$result=Database::query($sql);

		$i = 1;
		// while a record is found
		$doubt_data = null;
		while ($object = Database::fetch_object($result)) {
		    if ($questionType['type'] == UNIQUE_ANSWER_NO_OPTION && $object->position == 666) {
		        $doubt_data = $object;
                continue;
		    }
            $this->answer[$i] = $object->answer;
            $this->correct[$i] = $object->correct;
            $this->comment[$i] = $object->comment;
            $this->weighting[$i] = $object->ponderation;
            $this->position[$i] = $object->position;
            $this->hotspot_coordinates[$i] = $object->hotspot_coordinates;
            $this->hotspot_type[$i] = $object->hotspot_type;
            $this->destination[$i] = $object->destination;
            $this->autoId[$i] = $object->id_auto;
            $this->iid[$i] = $object->iid;
            $i++;
		}

		if ($questionType['type'] == UNIQUE_ANSWER_NO_OPTION && !empty($doubt_data)) {
            $this->answer[$i] = $doubt_data->answer;
            $this->correct[$i] = $doubt_data->correct;
            $this->comment[$i] = $doubt_data->comment;
            $this->weighting[$i] = $doubt_data->ponderation;
            $this->position[$i] = $doubt_data->position;
            $this->hotspot_coordinates[$i] = $object->hotspot_coordinates;
            $this->hotspot_type[$i] = $object->hotspot_type;
            $this->destination[$i] = $doubt_data->destination;
            $this->autoId[$i] = $doubt_data->id_auto;
            $this->iid[$i] = $doubt_data->iid;
            $i++;
	    }
        $this->nbrAnswers = $i-1;
	}

	/**
	 * returns the autoincrement id identificator
	 *
	 * @author Juan Carlos Ra�a
	 * @return integer - answer num
	 */
    public function selectAutoId($id)
    {
		return isset($this->autoId[$id]) ? $this->autoId[$id] : 0;
	}

	/**
	 * returns the number of answers in this question
	 *
	 * @author Olivier Brouckaert
	 * @return integer - number of answers
	 */
	public function selectNbrAnswers()
    {
		return $this->nbrAnswers;
	}

	/**
	 * returns the question ID which the answers belong to
	 *
	 * @author Olivier Brouckaert
	 * @return integer - the question ID
	 */
	public function selectQuestionId()
    {
		return $this->questionId;
	}

	/**
	 * returns the question ID of the destination question
	 *
	 * @author Julio Montoya
	 * @param integer $id
	 * @return integer - the question ID
	 */
	public function selectDestination($id)
    {
		return isset($this->destination[$id]) ? $this->destination[$id] : null;
	}

    /**
	 * returns the answer title
	 *
	 * @author Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return string - answer title
	 */
	public function selectAnswer($id)
	{
		return isset($this->answer[$id]) ? $this->answer[$id] : null;
	}

	/**
	 * return array answer by id else return a bool
	 * @param integer $auto_id
	 */
	public function selectAnswerByAutoId($auto_id)
	{
		$TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);

		$auto_id = intval($auto_id);
		$sql = "SELECT id, answer, id_auto FROM $TBL_ANSWER
				WHERE c_id = {$this->course_id} AND id_auto='$auto_id'";
		$rs = Database::query($sql);

		if (Database::num_rows($rs) > 0) {
			$row = Database::fetch_array($rs, 'ASSOC');

			return $row;
		}

		return false;
	}

    /**
     * returns the answer title from an answer's position
     *
     * @author Yannick Warnier
     * @param - integer $id - answer ID
     * @return bool - answer title
     */
	public function selectAnswerIdByPosition($pos)
	{
		foreach ($this->position as $k => $v) {
			if ($v != $pos) {
				continue;
			}

			return $k;
		}

		return false;
	}

    /**
     * Returns a list of answers
     * @author Yannick Warnier <ywarnier@beeznest.org>
     * @return array	List of answers where each answer is an array
     * of (id, answer, comment, grade) and grade=weighting
     */
    public function getAnswersList($decode = false)
     {
	 	$list = array();
         for ($i = 1; $i <= $this->nbrAnswers; $i++) {
             if (!empty($this->answer[$i])) {

	 			//Avoid problems when parsing elements with accents
	 			if ($decode) {
	        		$this->answer[$i] 	= api_html_entity_decode($this->answer[$i], ENT_QUOTES, api_get_system_encoding());
	        		$this->comment[$i]	= api_html_entity_decode($this->comment[$i], ENT_QUOTES, api_get_system_encoding());
	 			}

	 			$list[] = array(
                    'id' => $i,
                    'answer' => $this->answer[$i],
                    'comment' => $this->comment[$i],
                    'grade' => $this->weighting[$i],
                    'hotspot_coord' => $this->hotspot_coordinates[$i],
                    'hotspot_type' => $this->hotspot_type[$i],
                    'correct' => $this->correct[$i],
                    'destination' => $this->destination[$i]
				);
            }
	 	}

	 	return $list;
	 }

	/**
	 * Returns a list of grades
	 * @author Yannick Warnier <ywarnier@beeznest.org>
	 * @return array	List of grades where grade=weighting (?)
	 */
    public function getGradesList()
     {
	 	$list = array();
	 	for ($i = 0; $i<$this->nbrAnswers;$i++){
	 		if(!empty($this->answer[$i])){
	 			$list[$i] = $this->weighting[$i];
	 		}
	 	}
	 	return $list;
	 }

	 /**
	  * Returns the question type
	  * @author	Yannick Warnier <ywarnier@beeznest.org>
	  * @return	integer	The type of the question this answer is bound to
	  */
    public function getQuestionType()
     {
	 	$TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
	 	$sql = "SELECT type FROM $TBL_QUESTIONS
	 	        WHERE c_id = {$this->course_id} AND id = '".$this->questionId."'";
	 	$res = Database::query($sql);
	 	if (Database::num_rows($res)<=0){
	 		return null;
	 	}
	 	$row = Database::fetch_array($res);

	 	return $row['type'];
	 }


	/**
	 * tells if answer is correct or not
	 *
	 * @author Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return integer - 0 if bad answer, not 0 if good answer
	 */
    public function isCorrect($id)
	{
		return isset($this->correct[$id]) ? $this->correct[$id] : null;
	}

	/**
	 * returns answer comment
	 *
	 * @author Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return string - answer comment
	 */
    public function selectComment($id)
	{
        return isset($this->comment[$id]) ? $this->comment[$id] : null;
	}

	/**
	 * returns answer weighting
	 *
	 * @author Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @param integer $id
	 * @return integer - answer weighting
	 */
    public function selectWeighting($id)
	{
		return isset($this->weighting[$id]) ? $this->weighting[$id] : null;
	}

	/**
	 * returns answer position
	 *
	 * @author Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return integer - answer position
	 */
	function selectPosition($id)
	{
		return isset($this->position[$id]) ? $this->position[$id] : null;
	}

	/**
	 * returns answer hotspot coordinates
	 *
	 * @author	Olivier Brouckaert
	 * @param	integer	Answer ID
	 * @param integer $id
	 * @return	integer	Answer position
	 */
    public function selectHotspotCoordinates($id)
	{
		return isset($this->hotspot_coordinates[$id]) ? $this->hotspot_coordinates[$id] : null;
	}

	/**
	 * returns answer hotspot type
	 *
	 * @author	Toon Keppens
	 * @param	integer		Answer ID
	 * @param integer $id
	 * @return	integer		Answer position
	 */
    public function selectHotspotType($id)
	{
		return isset($this->hotspot_type[$id]) ? $this->hotspot_type[$id] : null;
	}

	/**
	 * Creates a new answer
	 *
	 * @author Olivier Brouckaert
	 * @param string 	$answer answer title
	 * @param integer 	$correct 0 if bad answer, not 0 if good answer
	 * @param string 	$comment answer comment
	 * @param integer 	$weighting answer weighting
	 * @param integer 	$position answer position
	 * @param array    $new_hotspot_coordinates Coordinates for hotspot exercises (optional)
	 * @param integer	$new_hotspot_type Type for hotspot exercises (optional)
     * @param string   $destination
	 */
    public function createAnswer(
        $answer,
        $correct,
        $comment,
        $weighting,
        $position,
        $new_hotspot_coordinates = null,
        $new_hotspot_type = null,
        $destination = ''
    ) {
		$this->new_nbrAnswers++;
        $id = $this->new_nbrAnswers;
        $this->new_answer[$id] = $answer;
        $this->new_correct[$id] = $correct;
        $this->new_comment[$id] = $comment;
        $this->new_weighting[$id] = $weighting;
        $this->new_position[$id] = $position;
        $this->new_hotspot_coordinates[$id] = $new_hotspot_coordinates;
        $this->new_hotspot_type[$id] = $new_hotspot_type;
        $this->new_destination[$id] = $destination;
	}

    /**
     * Updates an answer
     *
     * @author Toon Keppens
     * @param int $iid
     * @param string $answer
     * @param string $comment
     * @param string $correct
     * @param string $weighting
     * @param string $position
     * @param string $destination
     * @param string $hotspot_coordinates
     * @param string $hotspot_type
     */
    public function updateAnswers(
        $iid,
        $answer,
        $comment,
        $correct,
        $weighting,
        $position,
        $destination,
        $hotspot_coordinates,
        $hotspot_type
    ) {
        $answerTable = Database :: get_course_table(TABLE_QUIZ_ANSWER);

        $params = [
            'answer' => $answer,
            'comment' => $comment,
            'correct' => intval($correct),
            'ponderation' => $weighting,
            'position' => $position,
            'destination' => $destination,
            'hotspot_coordinates' => $hotspot_coordinates,
            'hotspot_type' => $hotspot_type
        ];

        Database::update($answerTable, $params, ['iid = ?' => intval($iid)]);
	}

	/**
	 * Records answers into the data base
	 *
	 * @author Olivier Brouckaert
	 */
    public function save()
    {
		$answerTable = Database::get_course_table(TABLE_QUIZ_ANSWER);
		$questionId = intval($this->questionId);

		$c_id = $this->course['real_id'];
        $correctList = [];
        $answerList = [];

		for ($i=1; $i <= $this->new_nbrAnswers; $i++) {
			$answer = $this->new_answer[$i];
			$correct = $this->new_correct[$i];
			$comment = $this->new_comment[$i];
			$weighting = $this->new_weighting[$i];
			$position = $this->new_position[$i];
			$hotspot_coordinates = $this->new_hotspot_coordinates[$i];
			$hotspot_type = $this->new_hotspot_type[$i];
			$destination = $this->new_destination[$i];
            $autoId = $this->selectAutoId($i);
            $iid = isset($this->iid[$i]) ? $this->iid[$i] : 0;

            if (!isset($this->position[$i])) {
                $params = [
                    'id_auto' => $autoId,
                    'c_id' => $c_id,
                    'question_id' => $questionId,
                    'answer' => $answer,
                    'correct' => intval($correct),
                    'comment' => $comment,
                    'ponderation' => $weighting,
                    'position' => $position,
                    'hotspot_coordinates' => $hotspot_coordinates,
                    'hotspot_type' => $hotspot_type,
                    'destination' => $destination
                ];
                $iid = Database::insert($answerTable, $params);
                if ($iid) {
                    $sql = "UPDATE $answerTable SET id = iid, id_auto = iid WHERE iid = $iid";
                    Database::query($sql);

                    $questionType = $this->getQuestionType();

                    if (in_array(
                        $questionType,
                        [MATCHING, MATCHING_DRAGGABLE]
                    )) {
                        $answer = new Answer($this->questionId);
                        $answer->read();

                        $correctAnswerId = $answer->selectAnswerIdByPosition($correct);
                        $correctAnswerAutoId = $answer->selectAutoId($correctAnswerId);

                        Database::update(
                            $answerTable,
                            ['correct' => $correctAnswerAutoId ? $correctAnswerAutoId : 0],
                            ['iid = ?' => $iid]
                        );
                    }
                }
            } else {
                // https://support.chamilo.org/issues/6558
                // function updateAnswers already escape_string, error if we do it twice.
                // Feed function updateAnswers with none escaped strings

                $this->updateAnswers(
                    $iid,
                    $this->new_answer[$i],
                    $this->new_comment[$i],
                    $this->new_correct[$i],
                    $this->new_weighting[$i],
                    $this->new_position[$i],
                    $this->new_destination[$i],
                    $this->new_hotspot_coordinates[$i],
                    $this->new_hotspot_type[$i]
                );
            }

            $answerList[$i] = $iid;

            if ($correct) {
                $correctList[$iid] = true;
            }
        }

        $questionType = self::getQuestionType();

        if ($questionType == DRAGGABLE) {
            foreach ($this->new_correct as $value => $status) {
                if (!empty($status)) {
                    $correct = $answerList[$status];
                    $myAutoId = $answerList[$value];

                    $sql = "UPDATE $answerTable
                            SET correct = '$correct'
                            WHERE
                                id_auto = $myAutoId
                            ";
                    Database::query($sql);
                }
            }
        }

        if (count($this->position) > $this->new_nbrAnswers) {
            $i = $this->new_nbrAnswers + 1;
            while ($this->position[$i]) {
                $position = $this->position[$i];
                $sql = "DELETE FROM $answerTable
                		WHERE
                			c_id = {$this->course_id} AND
                			question_id = '".$questionId."' AND
                			position ='$position'";
                Database::query($sql);
                $i++;
            }
        }

		// moves $new_* arrays
		$this->answer = $this->new_answer;
		$this->correct = $this->new_correct;
		$this->comment = $this->new_comment;
		$this->weighting = $this->new_weighting;
		$this->position = $this->new_position;
		$this->hotspot_coordinates = $this->new_hotspot_coordinates;
		$this->hotspot_type = $this->new_hotspot_type;

		$this->nbrAnswers = $this->new_nbrAnswers;
		$this->destination = $this->new_destination;
		// clears $new_* arrays

		$this->cancel();
	}

	/**
	 * Duplicates answers by copying them into another question
	 *
	 * @author Olivier Brouckaert
	 * @param  int question id
     * @param  array destination course info (result of the function api_get_course_info() )
     * @param string $newQuestionId
	 */
    public function duplicate($newQuestionId, $course_info = null)
    {
        if (empty($course_info)) {
            $course_info = $this->course;
        }

		$TBL_REPONSES = Database :: get_course_table(TABLE_QUIZ_ANSWER);
        $fixed_list = array();

        if (self::getQuestionType() == MULTIPLE_ANSWER_TRUE_FALSE ||
            self::getQuestionType() == MULTIPLE_ANSWER_TRUE_FALSE
        ) {
            // Selecting origin options
            $origin_options = Question::readQuestionOption(
                $this->selectQuestionId(),
                $this->course['real_id']
            );

            if (!empty($origin_options)) {
                foreach ($origin_options as $item) {
            	   $new_option_list[] = $item['id'];
                }
            }

            $destination_options = Question::readQuestionOption($newQuestionId, $course_info['real_id']);
            $i = 0;
            if (!empty($destination_options)) {
                foreach($destination_options as $item) {
                    $fixed_list[$new_option_list[$i]] = $item['id'];
                    $i++;
                }
            }
        }

		// if at least one answer
		if ($this->nbrAnswers) {
			// inserts new answers into data base
			$c_id = $course_info['real_id'];

			for ($i=1;$i <= $this->nbrAnswers;$i++) {
                if ($this->course['id'] != $course_info['id']) {
                    $this->answer[$i] = DocumentManager::replace_urls_inside_content_html_from_copy_course(
                        $this->answer[$i],
                        $this->course['id'],
                        $course_info['id']
                    );
                    $this->comment[$i] = DocumentManager::replace_urls_inside_content_html_from_copy_course(
                        $this->comment[$i],
                        $this->course['id'],
                        $course_info['id']
                    );
                }

				$answer = $this->answer[$i];
				$correct = $this->correct[$i];

                if (self::getQuestionType() == MULTIPLE_ANSWER_TRUE_FALSE ||
                    self::getQuestionType() == MULTIPLE_ANSWER_TRUE_FALSE
                ) {
                    $correct = $fixed_list[intval($correct)];
                }

				$comment = $this->comment[$i];
				$weighting = $this->weighting[$i];
				$position = $this->position[$i];
				$hotspot_coordinates = $this->hotspot_coordinates[$i];
				$hotspot_type = $this->hotspot_type[$i];
				$destination = $this->destination[$i];

                $params = [
                    'c_id' => $c_id,
                    'question_id' => $newQuestionId,
                    'answer' => $answer,
                    'correct' => $correct,
                    'comment' => $comment,
                    'ponderation' => $weighting,
                    'position' => $position,
                    'hotspot_coordinates' => $hotspot_coordinates,
                    'hotspot_type' => $hotspot_type,
                    'destination' => $destination
                ];
                $id = Database::insert($TBL_REPONSES, $params);

                if ($id) {
                    $sql = "UPDATE $TBL_REPONSES SET id = iid, id_auto = iid WHERE iid = $id";
                    Database::query($sql);
                }
			}
        }
	}

    /**
     * Get the necessary JavaScript for some answers
     * @return string
     */
    public function getJs()
    {
        //if ($this->questionId == 2)
        return "<script>
                jsPlumb.ready(function() {
                    if ($('#drag{$this->questionId}_question').length > 0) {
                        MatchingDraggable.init('{$this->questionId}');
                    }
                });
            </script>";
    }

}
