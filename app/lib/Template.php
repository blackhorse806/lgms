<?php

class Template {

	protected $latex = null;
	protected $file_name = null;
	protected $pub_pdf_name = null;
	protected $file_path = "C:\\xampp\\htdocs\\lgms.com\\lgms\\lg\\generation\\";
	protected $publish_path = "";
	protected $publish = false;

	function set_file_name($name) {
		$this->file_name = $name;
	}

	function set_file_path($path) {
		$this->file_path = $path;
	}

	public function load_template() {
		$this->latex = file_get_contents("C:\\xampp\\htdocs\\lgms.com\\lgms\\lg\\template\\latex_template.tex");
	}

	// Write latex code to file
	public function save_document() {
		if ($this->latex == null) {
			echo "Cannot save document, there is no latex code.";
			return;
		}
		if ($this->file_name == null) {
			echo "Cannot save document, the unit code is not set.";
			return;
		}
		// Create folder if it does not exist
		//mkdir("generation", 0755, true);
		// Create new document and write to it
		$doc = fopen($this->file_path . $this->file_name . ".tex", "w") or die("Unable to open file!");
		fwrite($doc, $this->latex);
		fclose($doc);
	}

	// Add latex code to template where tag is
	function populate_item($tag, $new_latex) {
		$this->latex = str_replace("\\newcommand{\\" . $tag ."}{}", "\\newcommand{\\" . $tag ."}{" . PrepareStringForLatex($new_latex) . "}", $this->latex);
	}

	function populate_item_latex($tag, $new_latex) {
		$this->latex = str_replace("\\newcommand{\\" . $tag ."}{}", "\\newcommand{\\" . $tag ."}{" . $new_latex . "}", $this->latex);
	}

	public function compile_latex() {

		// Set paths - dev
		//$published_path = "C:\\Apache24\\lg\\published\\" . $year . "\\" . $session . "\\";

		$latex_program = "C:\\Program Files (x86)\\MiKTeX 2.9\\miktex\\bin\\latex.exe";

		// Set paths - server
		// $published_path = "C:\\Apache24\\published\\" . $year . "\\" . $session . "\\";
		// $generation_path = "C:\\Apache24\\generation\\";
		// $latex_program = "C:\\Program Files (x86)\\MiKTeX 2.9\\miktex\\bin\\latex.exe";

		// Compile file into PDF
		shell_exec("\"" . $latex_program . "\" --output-format=pdf --interaction batchmode " . $this->file_path . $this->file_name . ".tex -output-directory=" . $this->file_path);
		// Do it again to generate table of contents
		shell_exec("\"" . $latex_program . "\" --output-format=pdf --interaction batchmode " . $this->file_path . $this->file_name . ".tex -output-directory=" . $this->file_path);

		// If state is published then copy file to published
		if ($this->publish) {
			shell_exec("copy \"" . $this->file_path . $this->file_name . ".pdf\" \"" . $this->publish_path .  $this->pub_pdf_name . ".pdf\"");
		}
	}
}


class LearningGuideGenerate extends Template {

	private $unit = null;

	public function __construct($unit = null) {
		$this->unit = $unit;
		if ($this->unit != null) {

			$this->set_file_name($this->unit->get_lg_id());
			$this->set_pdf_name($this->unit->get_lg_details());
			$this->load_template();

			$this->generate_unit_details();
			$this->generate_outcomes();
			$this->generate_modes_of_delivery();
			$this->generate_staff();
			$this->generate_feedback();
			$this->generate_approaches();
			$this->generate_assesment_summary();
			$this->generate_readings();
			$this->generate_contributions();
			$this->generate_assessments();
			$this->generate_activities();

			$this->save_document();
			$this->compile_latex();
		}
	}

	public function set_pdf_name($data)
	{
		$this->pub_pdf_name = $data['unit_code'] . '_' . $data['year'] . '_' . $data['session'] . '_' . $data['class'];
		//echo $this->pub_pdf_name;
	}

	public function generate_unit_details() {
		$details = $this->unit->attribute('core')->get();

		if ($details['state'] == "Published") {
			$this->publish_path = "C:\\xampp\\htdocs\\lgms.com\\lgms\\lg\\published\\" . $details['year'] . "\\" . $details['session'] . "\\";
			$this->publish = true;
            $this->latex = str_replace("{watermark_package}", "", $this->latex);
		}
		elseif ($details['state'] == "Approved") {
			$this->latex = str_replace("{watermark_package}", "", $this->latex);
		}else {
            $this->latex = str_replace("{watermark_package}", "\usepackage{draftwatermark}\n\SetWatermarkText{Unapproved}\n\SetWatermarkScale{5}", $this->latex);
        }

		// Prepare possible NA fields
		$details["assumed_knowledge"] = PrepareStringForLatex($details["assumed_knowledge"]);
		$details["essential_equipment"] = PrepareStringForLatex($details["essential_equipment"]);
		$details["legislative_prerequisites"] = PrepareStringForLatex($details["legislative_prerequisites"]);
		$details["prerequisites"] = PrepareStringForLatex($details["prerequisites"]);
		$details["corequisites"] = PrepareStringForLatex($details["corequisites"]);

		// Convert empty fields into NA
		$this->MakeNA($details["assumed_knowledge"]);
		$this->MakeNA($details["essential_equipment"]);
		$this->MakeNA($details["legislative_prerequisites"]);
		$this->MakeNA($details["prerequisites"]);
		$this->MakeNA($details["corequisites"]);

		// Populate all that can be NA - allows latex
		$this->populate_item_latex("AssumedKnowledge", $details["assumed_knowledge"]);
		$this->populate_item_latex("EssentialEquipment", $details["essential_equipment"]);
		$this->populate_item_latex("Legislative", $details["legislative_prerequisites"]);
		$this->populate_item_latex("PreRequisites", $details["prerequisites"]);
		$this->populate_item_latex("CoRequisites", $details["corequisites"]);

		// Populate all that cannot be NA
		$this->populate_item("UnitCode", $details["unit_code"]);
		$this->populate_item("UnitName", $details["unit_name"]);
		$this->populate_item("Session", $details["session"]);
		$this->populate_item("Year", $details["year"]);
		$this->populate_item("CreditPoints", $details["credit_points"]);
		$this->populate_item("UnitLevel", $details["unit_level"]);

		// Extra info for Online units
		$title_end = '';

		if ($details['class'] == 'Online') {
			$title_end = '(Online)';
			//echo "Online!";
		}

		$this->populate_item("TitleEnd", $title_end);
		//$this->populate_item("AssumedKnowledge", $details["assumed_knowledge"]);
		//$this->populate_item("PreRequisites", $details["prerequisites"]);
		//$this->populate_item("CoRequisites", $details["corequisites"]);
		$this->populate_item("Summary", $details["handbook_summary"]);
		$this->populate_item("RequirementsAttendance", $details["attendance_requirements"]);
		$this->populate_item("RequirementsOnline", $details["online_learning_requirements"]);
		//$this->populate_item("EssentialEquipment", $details["essential_equipment"]);
		//$this->populate_item("Legislative", $details["legislative_prerequisites"]);
		$this->populate_item("OutcomesIntro", $details["learning_outcomes_intro"]);
		$this->populate_item("ApproachIntro", $details["approach_to_learning"]);
		$this->populate_item("PassCriteria", $details["pass_criteria"]);
		$this->populate_item("AssFeedback", $details["assessment_feedback"]);
	}

	public function generate_outcomes(){
		// Get table data from database
		$outcomes = $this->unit->attribute("outcomes")->get();
		// Start building table latex
		$str = "\def\arraystretch{1.5}%\n";
		$str .= "\begin{tabulary}{\linewidth}{|>{\centering}l|L|}\n\\rowcolor[HTML]{C0C0C0}\n\hline\n\t& \\textbf{Outcome} \\\\\\hline\n";
		// Add data to table
		foreach ($outcomes as $row) {
			$str .= "\t" . implode(" & ", [PrepareStringForLatex($row["number"]), PrepareStringForLatex($row["outcome"])]) . "\\\\\\hline\n";
		}
		// Close the table
		$str .=  "\\end{tabulary}\n\\\\\n";
		// Add to document
		$this->populate_item_latex("Outcomes", $str);
	}


	public function generate_modes_of_delivery(){
		// Get table data from database
		$delivery = $this->unit->attribute("modes_of_delivery")->get();
		if ($delivery == null) {
			return;
		}
		// Start building table latex
		$str = "\\def\\arraystretch{1.5}%\n";
		$str .= "\\begin{tabulary}{\\linewidth}{| >{\\centering}m{2cm}|C|}\n";
		$str .= "\\rowcolor[HTML]{C0C0C0}\n\t\\hline\n";
		$str .= "\t\\textbf{Mode} & \\textbf{Hours} \\\\\\hline\n";
		// Add data to table
		foreach ($delivery as $row) {
			$row["hours"] = str_replace(".00", "", $row["hours"]);
			$str .= "\t" . implode(" & ", [PrepareStringForLatex($row["mode"]), PrepareStringForLatex($row["hours"])]) . "\\\\\\hline\n";
		}
		// Close the table
		$str .= "\\end{tabulary}\n";
		// Add to document
		$this->populate_item_latex("ModesOfDelivery", $str);
	}

	public function generate_staff(){
		$str = "";
		$str .= $this->generate_staff_type("Unit Coordinator");
		$str .= $this->generate_staff_type("Teaching Team");
		$str .= $this->generate_staff_type("Administrative Support");
		$this->populate_item_latex("Staff", $str);
	}

	public function generate_staff_type($staff_type) {
		// Get table data from database
		$data = $this->unit->attribute("staff")->get();
		if ($data == null) {
			return;
		}
		// Start building staff section
		$str = "\\begin{minipage}{24cm}\n";
		$str .= "\\noindent {\large \\textbf{" . PrepareStringForLatex($staff_type) . "}}\\\\\\\\\n";
		// Add tables for this staff type
		$count = 0;
		foreach ($data as $staff) {
			if ($staff["type"] === $staff_type) {
				// Setup a staff table
				$str .= "\def\arraystretch{1}%\n";
				$str .= "\begin{tabulary}{\linewidth}{LL}\n";
				if (strlen($staff["name"]) > 0) $str .= "\\textbf{Name:} & " . PrepareStringForLatex($staff["name"]) . " \\\\\n";
				if (strlen($staff["phone"]) > 0) $str .= "\\textbf{Phone:} & " . PrepareStringForLatex($staff["phone"]) . " \\\\\n";
				if (strlen($staff["location"]) > 0) $str .= "\\textbf{Location:} & " . PrepareStringForLatex($staff["location"]) . " \\\\\n";
				if (strlen($staff["email"]) > 0) $str .= "\\textbf{Email:} & " . PrepareStringForLatex($staff["email"]) . " \\\\\n";
				if (strlen($staff["consultation"]) > 0) $str .= " \multicolumn{2}{l}{\\textbf{Consultation Arrangement:}}\\\\\n \multicolumn{2}{l}{\pbox{17cm}{" . PrepareStringForLatex($staff["consultation"]) . "}} \\\\\\\\ \n";
				$str .=  "\\end{tabulary}\n\\\\";
				$count++;
			}
		}
		// Close the table
		$str .= "\\\\\n\\end{minipage}";
		$str .= "\\\\\\\\\n";
		// Check if there were any staff
		if ($count == 0) {
			return "";
		}
		return $str;
	}

	function generate_feedback() {
		$data = $this->unit->attribute("feedback")->get();
		if ($data == null) {
			return;
		}
		$str = "";
		$str .= "As a result of student feedback, the following changes and improvements have recently been made:\n";
		$str .= "\\vspace{-\\topsep}\n\begin{itemize}\n\setlength{\itemsep}{-5pt plus 0pt}\n";
		foreach ($data as $row) {
			$str .= "\item " . PrepareStringForLatex(ucfirst($row["feedback_item"])) . "\n";
		}
		$str .=  "\\end{itemize}\n\\vspace{-\\topsep}\n\\\\";
		$this->populate_item_latex("FeedbackChanges", $str);
	}


	function generate_approaches(){
		$str = "";
		$data = $this->unit->attribute("approaches")->get();
		if ($data != null) {
			$str .= "\\def\\arraystretch{1.5}%\n";
			$str .= "\\begin{tabulary}{\\linewidth}{| >{\\centering}l|L|}";
			$str .= "\\rowcolor[HTML]{C0C0C0}";
			$str .= "\\hline";
			$str .= "\\vspace{.5\\baselineskip}\\textbf{Type}\\vspace{.5\\baselineskip} & \\vspace{-.6\baselineskip}\\textbf{Approach} \\\\";
			$str .= "\\hline\n";
			foreach ($data as $row) {
				$mode = PrepareStringForLatex($row["mode"]);
				// Return with no ouput if there is no data
				if (strtoupper($mode) == "VUWS" && $row["approach"] == "") {
					return;
				}
				// Don't show vUWS if there is no data in it
				if (!(strtoupper($mode) == "VUWS" && $row["approach"] == "")) {
					if (strtoupper($mode) <> "VUWS") $mode = ucwords(strtolower($mode));
					$str .= " " . $mode . " &  \pbox{14cm}{\\vspace{.5\baselineskip} " . PrepareStringForLatex($row["approach"]) . "\\vspace{.5\baselineskip}} \\ \\\\\n\hline\n";
				}
			}
			$str .=  "\\end{tabulary}\n\\\\";
			$this->populate_item_latex("Approach", $str);
		}
	}

	function generate_assesment_summary(){
		$str = "";
		$data = $this->unit->attribute("assessment_summary")->get();
		if ($data != null) {
			$str .= "\def\arraystretch{1.5}%\n";
			$str .= "\begin{tabulary}{\linewidth}{";
			$str .= "|>{\\raggedright\arraybackslash}p{4cm}";		// Item
			$str .= "|>{\\raggedright\arraybackslash}p{1.2cm}";		// Weight
			$str .= "|>{\\raggedright\arraybackslash}p{6cm}";		// Due Date
			$str .= "|>{\\raggedright\arraybackslash}p{2.55cm}";	// ULOs
			$str .= "|>{\\raggedright\arraybackslash}p{1.5cm}|}";	// Threshold
			$str .= "\\rowcolor[HTML]{C0C0C0}";
			$str .= "\hline";
			$str .= "\\textbf{Item} & \\textbf{Weight} & \\textbf{Due Date} & \\textbf{ULO's Assessed} & \\textbf{Threshold} \\\\";
			$str .= "\hline\n";
			foreach ($data as $row) {
				// Convert Threshold from 0 & 1 to No & Yes
				$threshold = "";
				if ($row["threshold"] == "0") {
					$threshold = "No";
				} else {
					$threshold = "Yes";
				}
				// If ULOs are empty then replace with "Not Applicable"
				$row["ulos"] = PrepareStringForLatex($row["ulos"]);
				//MakeNA($row["ulos_assessed"]);
				// Output row to string
				$str .= " " . PrepareStringForLatex(ucfirst($row["name"])) . " & ";
				$str .= PrepareStringForLatex($row["weight"] . "%") . " & ";
				$str .= PrepareStringForLatex($row["due_date"]) . " & ";
				$str .= $row["ulos"] . " & ";
				$str .= $threshold . "\\\\\n\hline\n";
			}
			$str .=  "\\end{tabulary}\n\\\\";
		}
		$this->populate_item_latex("AssSummary", $str);
	}


	function generate_readings(){
		$str = "";
		$str .= $this->generate_individual_reading_list("Prescribed Textbook");
		$str .= $this->generate_individual_reading_list("Essential Reading");
		$str .= $this->generate_individual_reading_list("Additional Reading");
		$str .= $this->generate_individual_reading_list("Online Resource");
		$str .= $this->generate_individual_reading_list("Literacy and/or Numeracy Resource");
		$this->populate_item_latex("Readings", $str);
	}

	function generate_individual_reading_list($r_type) {
		$str = "";
		$count = 0;
		$data = $this->unit->attribute("readings")->get();
		//var_dump($data);
		if ($data != null) {
			$str .= "\\noindent {\large \\textbf{" . PrepareStringForLatex($r_type) . "}}\\\\\n";
			$str .= "\begin{itemize}[noitemsep,topsep=0pt]\n";
			foreach ($data as $row) {
				if ($row["resource_type"] == $r_type) {
					if (strlen($row["reference"]) > 0) {
						$str .= "\item " . hyperlink(PrepareStringForLatex($row["reference"])) . "\n";
						$count++;
					}
				}
			}
			$str .= "\\end{itemize}\n";
			$str .= "\\vspace{.5cm}\n";
		}
		if ($count == 0) {
			$str = "";
		}
		return $str;
	}

	// Generates the CLO table
	function generate_contributions() {
		// Get the unit clo table data
		$clos = $this->unit->attribute("clos")->get();
		$ulos = $this->unit->attribute("outcomes")->get();
		if ($clos == null || $ulos == null) {
			return;
		}
		// Create new landscape page
		$str = "";
		$str .= "\begin{landscape}\n";
		$str .= "\subsection{Contribution to Course Learning Outcomes}\n";
	    //Loop over each clo table and create it
		$tables = "";
		$isReduced = false;
		$wasReduced = false;
	    foreach ($clos as $course) {
			// Get clo and ulo counts
			$clo_count = count($course["course_outcomes"]);
			$ulo_count = count($ulos);
			// Calculate if table needs to be reduced
			if ($ulo_count > 8 || $clo_count > 13) {
				$isReduced = true;
				$wasReduced = true;
			} else {
				$isReduced = false;
			}
			// Generate table
			$tables .= $this->generate_course_table($isReduced, $course, $ulos);
		}
		// Add the key if tables were reduced
		if ($wasReduced) {
			$str .= "Key: (I)ntroduced (D)eveloped (A)ssured\\\\\n";
		}
		// Join tables to latex string
		$str .=  $tables . "\\end{landscape}\n";
		// Add latex to template
		$this->populate_item_latex("OutcomesContribution", $str);
	}


	function generate_course_table($isReduced, $course) {
		// Exit if something is not right
		if ($course["contributions"] == null) {
			echo "Course CLO's are missing for clo table.";
			return;
		}
		$clo_count = count($course["course_outcomes"]);
		$str = "";
		// Decided if new page is needed based on table size
		if ($clo_count < 14) {
			$str .= "\\begin{minipage}{24cm}\n";
		} else {
			$str .= "\\newpage\n";
		}
		// Add course title
		$code = "";
		if (PrepareStringForLatex($course["course_code"]) != "1") {
			$code = PrepareStringForLatex($course["course_code"]) . ": ";
		}
		$str .= "\\hfill\\break\\\\\n\\noindent \\textbf{" . $code . PrepareStringForLatex($course["course_name"]) . "}\\\\\n";
		// Add course introduction to table string
		if ($course["course_intro"] != "") {
			$str .= "\\\\\n" . PrepareStringForLatex($course["course_intro"]) . "\\\\\n";
		}
		// Build table
		$str .= "\\def\arraystretch{1}%\n";
		$str .= "\\begin{tabulary}{";
		// Adjust for different ulo sizes
		if ($course["num_ulos"] < 16) {
			$str .= "1";
		} else {
			$str .= "1.1";
		}
		$str .= "\\linewidth}{|L|C|C|C|C|C|C|C|C|C|C|C|C|C|C|C|C|C|C|C|C|C|}\\hline \n";
		// Add table title in first cell
		if ($course["course_code"] == "1") {
			$str .= "\tGraduate Attributes";
		} else {
			$str .= "\tCourse Learning Outcomes";
		}
		// Add ulo table headings
		for ($i=0; $i < $course["num_ulos"]; $i++) {
			// Add each ULO number
			if (!$isReduced) {
				$str .= "& ULO " . ($i + 1);
			} else {
				$str .= "& " . ($i + 1);
			}
		}
		$str .= "\\\\\\hline\n";
		// Generate each row of the table
		$clo = 0;
		foreach ($course["contributions"] as $row) {
			// Add course outcome
			$str .=  "\t" . PrepareStringForLatex(ucfirst($course["course_outcomes"][$clo]["num"] . ". " . $course["course_outcomes"][$clo]["outcome"])) . " & ";
			// Loop over each ulo (col)
			$row_cells = [];
			foreach ($row as $cell) {
				// Add a contribution
				if ($cell != null) {
					// Adjust for number of ulos
					if (!$isReduced) {
						switch ($cell["contribution"]) {
							case "I":
								array_push($row_cells, "Introduced");
								break;
							case "A":
								array_push($row_cells, "Assured");
								break;
							case "D":
								array_push($row_cells, "Developed");
								break;
						}
					} else {
						array_push($row_cells, $cell["contribution"]);
					}
				} else {
					array_push($row_cells, " ");
				}
			}
			$str .= implode(" & ", $row_cells);
			// Add horizontal line rule for each row
			$str .= "\\\\\\hline\n";
			$clo++;
		}
		// Close table
		$str .=  "\\end{tabulary}\n";
		// Add minipage if no page break used
		if ($clo_count < 14) {
			$str .= "\\end{minipage}\n\\\\\\\\\\\\\n";
		} else {
			$str .= "\\\\\n";
		}
		return $str;
	}


	function generate_assessments(){
		$assessments = $this->unit->attribute("assessments")->get();
		if ($assessments == null) {
			return null;
		}
		$str = "";
		foreach ($assessments as $ass) {
			$str .= "\subsubsection{" . PrepareStringForLatex(ucfirst($ass["name"])) . "}\n";
			$str .= "\def\arraystretch{1.5}%\n\begin{tabulary}{\linewidth}[t]{|l|>{\\raggedright\arraybackslash}p{13cm}|}\\hline\n";
			$str .= "\\textbf{Weight: } & " . PrepareStringForLatex($ass["weight"]) . "\%\\\\\n\\hline";
			$str .= "\\textbf{Type of Collaboration: } & " . PrepareStringForLatex($ass["collaboration"]) . "\\\\\n\\hline";
			$str .= "\\textbf{Due: } & " . PrepareStringForLatex($ass["due_date"]) . "\\\\\n\\hline";
			$str .= "\\textbf{Submission: } & " . PrepareStringForLatex($ass["submission"]) . "\\\\\n\\hline";
			$str .= "\\textbf{Format: } & " . PrepareStringForLatex($ass["format"]) . "\\\\\n\\hline";
			$str .= "\\textbf{Length: } & " . PrepareStringForLatex($ass["length"]) . "\\\\\n\\hline";
			$str .= "\\textbf{Curriculum Mode: } & " . PrepareStringForLatex($ass["mode"]) . "\\\\\n\\hline";
			if ($ass["threshold"] == "1") {
				$str .= "\\textbf{Threshold Detail: } & " . PrepareStringForLatex($ass["threshold_text"]) . " \\\\\n\\hline";
			}
			$str .=  "\\end{tabulary}\\vspace{.25cm}\n\\\\\n";
			$str .= "\\textbf{Instructions: }\\\\\n";
			// Instructions
			$str .= "\n\\noindent " . html2latex($ass["instructions"]) . "\\\\\\\\\n";
			// Exemplar
			if ($ass["exemplar"] == "1") {
				$str .= "\\textbf{Exemplar: }"  . "\\\\\n";
				$str .= "\n\\noindent " . html2latex($ass["exemplar_text"]) . "\\\\\\\\\n";
			}
			// Resources
			if (trim($ass["resources"]) != "") {
				$str .= "\\textbf{Resources: } \\\\\n";
				$str .= "" . PrepareStringForLatex($ass["resources"]) . "\\\\\\\\\n";
			}
			// MArking Criteria
			$str .= $this->generate_marking_criteria($ass);
			$str .= "\n\\newpage\n";
		}
		$this->populate_item_latex("AssDetail", $str);
	}

	function generate_marking_criteria($ass) {
		$str = "";
		// Figure out type
		if ($ass["marking_criteria_type"] == "none") {
			return;
		}
		// Create heading
		$str .= "\\textbf{Marking Criteria: } \\\\\n";
		// Output depending on type
		// Plain
		if ($ass["marking_criteria_type"] == "plain") {
			$str .= "" . PrepareStringForLatex($ass["marking_criteria_plain"]) . "\\\\\n";
		}
		// Rich
		if ($ass["marking_criteria_type"] == "rich") {
			$str .= "" . html2latex($ass["marking_criteria_rich"]) . "\\\\\n";
		}
		// Table
		if ($ass["marking_criteria_type"] == "table") {
			$str .= "\\footnotesize{\n";
			$str .= "\\vspace{-.5cm}\n";
			$col_width = 15 / 6;
			$cw = ">{\\raggedright\arraybackslash}p{" . $col_width."cm}";
			$str .= "\\begin{longtable}[c]{|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|} \hline \n";
			$str .= "\\rowcolor[HTML]{C0C0C0}\nCriteria & High Distinction & Distinction & Credit & Pass & Unsatisfactory \\\\\n\hline\n";
			$marking_criterias = $this->unit->attribute("marking_criteria")->get();
			if ($marking_criterias == null) {
				return null;
			}
			$ass_marking_criteria = null;
			foreach ($marking_criterias as $marking_criteria) {
				if ($marking_criteria["id"] === $ass["id"]) {
					$ass_marking_criteria = $marking_criteria["table"];
					break;
				}
			}
			if ($ass_marking_criteria == null) {
				return null;
			}
			foreach ($ass_marking_criteria as $row) {
				if ($row["ass_id"] === $ass["id"]) {
					$str .= PrepareStringForLatex($row["criteria"]) . " & ";
					$str .= PrepareStringForLatex($row["hd"]) . " & ";
					$str .= PrepareStringForLatex($row["d"]) . " & ";
					$str .= PrepareStringForLatex($row["c"]) . " & ";
					$str .= PrepareStringForLatex($row["p"]) . " & ";
					$str .= PrepareStringForLatex($row["f"]);
					$str .= "\\\\\n\hline\n";
				}
			}
			// Close table
			$str .=  "\\end{longtable}\n";
			$str .= "}\\normalsize\n";
		}
		return $str;
	}



	function generate_activities(){
		$str = "";
		$columns = null;
		$data = null;
		$ass = null;

		$num_weeks = 0;
		$num_cols = 0;

		$data = $this->unit->attribute("activity_data")->get();

		$columns = $this->unit->attribute("activity_columns")->get();
		$num_cols = count($columns);

		$weeks = $this->unit->attribute("session_weeks")->get();
		$num_weeks = count($weeks);

		$ass = $this->unit->attribute("activity_assessments")->get();

		// If no data to generate activites table
		if (($num_cols == 0 || $num_weeks == 0) && $ass == null) {
			$str = "\\stepcounter{section}\n";
			// echo "cols: " . $num_cols . "<br>";
			// echo "num_weeks: " . $num_weeks . "<br>";
			return $str;
		}

		$str .= "\\begin{landscape}\n";
		$str .= "\\section{Teaching and Learning Activities}\n";

		// Build table
		$str .= "\\small{";
		$str .= "\\vspace{-.5cm}\n";
		//$str .= "\begin{center}\n";
		$col_width = (23.5 - 1.75) / ($num_cols + 1);
		$cw = ">{\\raggedright\arraybackslash}p{" . $col_width."cm}";
		$str .= "\\def\arraystretch{1}%\n";
		$str .= "\\noindent\\begin{longtable}[c]{|>{\\raggedright\arraybackslash}p{1.6cm}|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|".$cw."|} \hline \n";
		for ($i=0; $i <= $num_weeks; $i++) {
			if ($i != 0) {
				if ($weeks[$i-1]["week_type"] != "Week") {
					$str .= "\\rowcolor[HTML]{CCCCCC}\n";
				}
				$y = date("Y", strtotime($weeks[$i-1]["week_date"]));
				$m = date("m", strtotime($weeks[$i-1]["week_date"]));
				$day = date("d", strtotime($weeks[$i-1]["week_date"]));
				$str .= "Week " . $i . "\\newline\n" . $day . "-" . $m . "-" . $y . " & ";
			} else {
				$str .= "\\textbf{Weeks} & ";
			}

			if ($columns != null) {
				foreach ($columns as $col) {
					if ($i == 0) {
						$str .= "\\textbf{" . $col["col_name"] . "}";
					} else {
						foreach ($data as $d) {
							if ($d["col_name"] == $col["col_name"] && $d["week"] == $i) {
								$str .= PrepareStringForLatex($d["data"]);
							}
						}
					}
					if ($col != $columns[$num_cols - 1]) {
						$str .= " & ";
					} else {
						if ($i == 0) {
							$str .= " & \\textbf{Assessments Due}";
						} else {
							$str .= " & ";
							$weeks_ass = array();
							if ($ass != null) {
								foreach ($ass as $a) {
									if ($a["week"] == $i && $a["isChecked"] == "1") {
										array_push($weeks_ass, $a["name"]);
									}
								}
							}
							for ($wa=0; $wa < count($weeks_ass); $wa++) {
								$str .= "\\color{red} - " . PrepareStringForLatex($weeks_ass[$wa]) . " \\color{black} ";
								if ($wa != count($weeks_ass) - 1) {
									$str .= "\\newline\n";
								}
							}

						}
					}
				}
			} else {
				// Handles when activities table only has assessments
				if ($i == 0) {
						$str .= "\\textbf{Assessments Due}";
					} else {
						$weeks_ass = array();
						if ($ass != null) {
							foreach ($ass as $a) {
								if ($a["week"] == $i) {
									array_push($weeks_ass, $a["short_title"]);
								}
							}
						}
						for ($wa=0; $wa < count($weeks_ass); $wa++) {
							$str .= "\\color{red} - " . PrepareStringForLatex($weeks_ass[$wa]) . " \\color{black} ";
							if ($wa != count($weeks_ass) - 1) {
								$str .= "\\newline\n";
							}
						}

					}
			}

			$str .= "\\\\\n\hline\n";
		}

		// Close table
		$str .=  "\\end{longtable}\n";
		//$str .= "\\end{center}";
		$str .= "}";
		$str .= "\\vspace{-.4cm}\n";
		$str .= "\\indent\\footnotesize{The above timetable should be used as a guide only, as it is subject to change. Students will be advised of any changes as they become known.}";

		$str .= "\\end{landscape}\n";
		$str .= "\\newpage\n";

		$this->populate_item_latex("Activities", $str);
	}


	function MakeNA(&$str) {
		if ($str == null || $str == "") {
			$str = "\\textcolor{gray}{Not Applicable}";
		}
	}
}




function ExtractLatexTags(&$html, $keepTag) {

	//$html = htmlspecialchars_decode($html);
	//<p>&lt;latex&gt;\textbf{testing}&lt;/latex&gt;</p>
	$start = 0;
	$finish = 0;
	$latexTags = null;

	$taglen = 9;

	$start = strpos($html, htmlspecialchars("\<latex\>"));
	$finish = strpos($html, htmlspecialchars("\</latex\>"));

	if ($start == null) {
		$start = strpos($html, "<latex>");
		$taglen = 7;
	}

	if ($finish == null) {
		$finish = strpos($html, "</latex>");
	}

	//echo $start;

	if ($start == 0 || $finish == 0) {
		return;
	}

	if ($start > 0) {
		 //echo $html . " " . $start . "-" . $finish . "\n";
		if ($keepTag == true) {
			$latexTags = substr($html, $start, $finish-$start+$taglen);
		} else {
			$latexTags = substr($html, $start + $taglen, $finish-$start-$taglen);
		}
		//echo $latexTags;

		$before = substr($html,0, $start);
		$between = "######";
		$after = substr($html, $finish + $taglen + 1, strlen($html));

		$html = $before . $between . $after;
	   // echo $html;
	}

	return $latexTags;
}

function PrepareStringForLatex($str) {
	$str = htmlspecialchars_decode($str);

	$latex = null;
	if (strpos($str, "<latex>")) {
		//echo "Found!\n". $str;
		$latex = ExtractLatexTags($str, false);
	}

	// Fix \
	$str = str_replace("\\", "\\textbackslash ", $str);

	// Fix &nbsp;
	$str = str_replace("&nbsp;", "\ ", $str);

	// Fix {
	$str = str_replace("{", "\{", $str);

	// Fix }
	$str = str_replace("}", "\}", $str);

	// Fix ~
	$str = str_replace("~", "\\textasciitilde ", $str);

	// Fix ^
	$str = str_replace("^", "\\textasciicircum ", $str);

	// Fix Dashes
	$str = str_replace(array('-','–','-','—', chr(150), chr(151)), '-', $str);

	// Fix bullet point
	$str = str_replace("•", "- ", $str);

	// Fix new lines
	$str = str_replace("\r", "", str_replace("\n", "\\\\\n", $str));

	// Fix %
	$str = str_replace("%", "\%", $str);

	// Fix &
	$str = str_replace("&", "\&", $str);

	// Fix $
	$str = str_replace("$", "\\$", $str);

	// Fix #
	$str = str_replace("#", "\#", $str);

	// Fix _
	$str = str_replace("_", "\\_", $str);

	// Fix <
	$str = str_replace("<", "\\textless", $str);

	// Fix >
	$str = str_replace(">", "\\textgreater", $str);

	// Fix \\
	$str = str_replace("\\\\", "\\newline", $str);

	// Fix character 12
	$str = str_replace(chr(12), "", $str);

	if ($latex != null) {
		$str = str_replace("\#\#\#\#\#\#", $latex, $str);
	}

	return $str;
}

function hyperlink($str) {
	// Initialise variables
	$newstr = "";
	$lastStart = -1;
	$lastEnd = 0;
	// Loop over each link
	while (true) {
		// Reset nextLink
		$nextLink = strlen($str);
		// Find the next http or https
		if (strpos($str, "http://", $lastEnd) != null && strpos($str, "http://", $lastEnd) < $nextLink) {
			$nextLink = strpos($str, "http://", $lastEnd);
		}
		if (strpos($str, "https://", $lastEnd) != null && strpos($str, "https://", $lastEnd) < $nextLink) {
			$nextLink = strpos($str, "https://", $lastEnd);
		}
		// If no more links then return
		if ($nextLink == strlen($str)) {
			if ($lastStart == -1) {
				return $str;
			} else {
				return $newstr . substr($str, $lastEnd, strlen($str) - $lastEnd);
			}
		}
		// Set start position
		$start = $nextLink;
		// Set end position
		$end = strpos($str, " ", $start);
		if (($end > strpos($str, "\\newline", $start) && strpos($str, "\\newline", $start) != null) || $end == null) {
			$end = strpos($str, "\\newline", $start);
		}
		if ($end == null) {
			$end = strlen($str);
		}
		// Check for a fullstop at the end of the sentence breaking link
		if ($str[$end - 1] == "." || $str[$end - 1] == ",") {
			$end -= 1;
		}
		// Get link string
		$link = substr($str, $start, $end-$start);
		$link = str_replace("\\newline", " ", $link);
		// Convert into latex url
		$latexhref = "\\url{" . $link . "}";
		// Write old string text and new link to return string
		$newstr .= substr($str, $lastEnd, $start - $lastEnd) . $latexhref;
		// Set the last start position
		$lastStart = $start;
		$lastEnd = $end;
	}
}



function html2latex ($html, $within_table = false) {

    // Extract latex tags
    $latex = ExtractLatexTags($html, false);

    // Replace tags to more standard tags
    $html = str_replace("\n", " ", $html);
    $html = str_replace("<strong>", "<b>", $html);
    $html = str_replace("</strong>", "</b>", $html);
    $html = str_replace("<div><br></div>", "<br>", $html);
    $html = str_replace("<p>&nbsp;</p>", "<br>", $html);

    // Strip useless tags
    while (strpos($html, "<b></b>") !== false) {
        $html = str_replace("<b></b>", "", $html);
    }
    while (strpos($html, "<u></u>") !== false) {
        $html = str_replace("<u></u>", "", $html);
    }
    while (strpos($html, "<i></i>") !== false) {
        $html = str_replace("<i></i>", "", $html);
    }
    while (strpos($html, "<p></p>") !== false) {
        $html = str_replace("<p></p>", "", $html);
    }
    while (strpos($html, "<div></div>") !== false) {
        $html = str_replace("<div></div>", "", $html);
    }

    // Split the input string into array
    $text = array();
    $text = explode('<', trim($html));
    $tableOn = false;
    $isFirstCol = true;
    $stopBr = false;
    $block_end_break = false;
    $block_start_break = true;
    $table_html = "";

    if (count($text)) {// html tags found
        for ($i=0; $i<count($text); $i++) {
            // Needs to be initialised
            $new_str = "";
            $ori_str = $text[$i];
            if (strlen($text[$i]) > 0 && strpos($text[$i], ">") == false) {
                //echo "" . strpos($text[$i], ">") ."Eh?" . $text[$i] . "\n";
                $block_start_break = false;
                $new_str = PrepareStringForLatex($text[$i]);
            }

            if ($tableOn && substr($ori_str, 0, 6) != '/table') {
                $table_html .= "<" . $ori_str;
                $text[$i] = "";
                continue;
            }

            // Determine if it is an opening or closing tag
            if (substr($ori_str, 0, 1) == '/') {
                // Closing tags
                // Search for the position of the closing '>'
                $pos = strcspn($ori_str, '>');
                $close_tag = substr($ori_str, 1, $pos-1);

                switch ($close_tag) {
                    case 'span':
                        $new_str = PrepareStringForLatex(substr($ori_str, $pos + 1));
                        break;
                    case 'div':
                        if ($block_end_break) {
                            $block_start_break = true;
                        } else {
                            $block_start_break = true;
                            $block_end_break = true;
                            if (!$stopBr) {
                                $new_str = "\\newline ";
                            }
                        }
                        $new_str .= PrepareStringForLatex(substr($ori_str, $pos + 1));
                        break;
                    case 'b':
                        $new_str = '}' . PrepareStringForLatex(substr($ori_str, 3));
                        break;
                    case 'i':
                        $new_str = '}' . PrepareStringForLatex(substr($ori_str, 3));
                        break;
                    case 'u':
                        $new_str = '}' . PrepareStringForLatex(substr($ori_str, 3));
                        break;
                    case 'ul':
                        $stopBr = false;
                        $new_str = "\n\\end{itemize}\n" . PrepareStringForLatex(substr($ori_str, 4));
                        break;
                    case 'li':
                        $new_str = '';
                        break;
                    case 'ol':
                        $stopBr = false;
                        $new_str = "\\end{enumerate}\n" . PrepareStringForLatex(substr($ori_str, 4));
                        break;
                    case 'table':
                        $table = new LatexTable($table_html);
                        $new_str = $table->get_latex() . PrepareStringForLatex(substr($ori_str, 7));
                        $table_html = "";
                        $tableOn = false;
                        break;
                    case 'p':
                        if ($block_end_break) {
                            $block_start_break = true;
                        } else {
                            $block_start_break = true;
                            $block_end_break = true;
                            if (!$stopBr) {
                                $new_str = "\\newline ";
                            }
                        }
                        $new_str .= PrepareStringForLatex(substr($ori_str, 3));
                        break;
                    case 'a':
                        $new_str = '' . PrepareStringForLatex(substr($ori_str, 3));
                        break;
                }
            }
            else {
                // Opening tags
                $wsp = strcspn($ori_str, ' ');
                $cls = strcspn($ori_str, '>');
                $pos = 0;

                if ($wsp < $cls) {
                    $pos = $wsp;
                } else {
                    $pos = $cls;
                }

                $open_tag = substr($ori_str, 0, $pos);

                switch ($open_tag) {
                    case 'span':
                        $new_str = PrepareStringForLatex(substr($ori_str, $cls + 1));
                        break;
                    case 'div':
                        if ($block_start_break) {
                            $block_end_break = false;
                        } else {
                            if (!$stopBr) {
                                $new_str = "\\newline ";
                            }
                            $block_start_break = true;
                        }
                        $new_str .= PrepareStringForLatex(substr($ori_str, $cls + 1));
                        break;
                    case 'br':
                        if ($stopBr) {
                            PrepareStringForLatex(substr($ori_str, $cls + 1));
                        } else {
                            $block_start_break = true;
                            $block_end_break = true;
                            $new_str = "\\newline " . PrepareStringForLatex(substr($ori_str, $cls + 1));
                        }
                        break;
                    case 'b':
                        $new_str = '\textbf{' . PrepareStringForLatex(substr($ori_str, $cls + 1));
                        break;
                    case 'i':
                        $new_str = '\textit{' . PrepareStringForLatex(substr($ori_str, $cls + 1));
                        break;
                    case 'u':
                        $new_str = '\ul{' . PrepareStringForLatex(substr($ori_str, $cls + 1));
                        break;
                    case 'ul':
                        // Remove previous line break
                        if (substr($text[$i-1], strlen($text[$i]) - 11, 10) == "\\newline ") {
                            $text[$i-1] = substr($text[$i-1], 0, strlen($text[$i]) - 11);
                        }
                        $stopBr = true;
                         if ($within_table) {
                            $new_str = "\\vspace{-.5cm}\n";
                            $new_str .= "\n\begin{itemize}[leftmargin=*]\setlength{\itemsep}{-5pt plus 0pt}\n";
                        } else {
                            $new_str = "\n\begin{itemize}\setlength{\itemsep}{-5pt plus 0pt}\n";
                        }
                        break;
                    case 'li':
                        $stopBr = true;
                        $new_str = "\n\item " . PrepareStringForLatex(substr($ori_str, $cls + 1));
                        break;
                    case 'ol':
                        // Remove previous line break
                        if (substr($text[$i-1], strlen($text[$i]) - 11, 10) == "\\newline ") {
                            $text[$i-1] = substr($text[$i-1], 0, strlen($text[$i]) - 11);
                        }
                        $stopBr = true;
                        if ($within_table) {
                            $new_str = "\\vspace{-.25cm}\n";
                            $new_str .= "\n\\begin{enumerate}[leftmargin=*]\n\\itemsep-0.5em\n";
                        } else {
                            $new_str .= "\n\\begin{enumerate}\n\\itemsep-0.5em\n";
                        }
                        break;
                    case 'table':
                        $tableOn = true;
                        $table_html = "<" . $ori_str;
                        break;
                    case 'p':
                        if ($block_start_break) {
                            $block_end_break = false;
                        } else {
                            if (!$stopBr) {
                               $new_str = "\\newline ";
                            }
                            $block_start_break = true;
                        }
                        $new_str .= PrepareStringForLatex(str_replace("\n", "", substr($ori_str, $cls + 1)));
                        break;
                    case 'a':
                        // Extract href
                        list($tag, $href, $rest) = explode('"', $ori_str);
                        $website = substr($rest, 1);
                        $new_str = '\\href{' . $href . '}{' . $website . '}';
                        break;
                }
            }
            // Write the updated text back to the array
            $text[$i] = $new_str;
        }
        // Compose the Tex string
        $tex_str = implode("", $text);
    }
    else {
        // No html tags found. Just return the string
        $tex_str = $text;
    }

    $tex_str = str_replace("\#\#\#\#\#\#", $latex, $tex_str);

    // Convert html entities to characters
    $tex_str = htmlspecialchars_decode($tex_str);
    return $tex_str;
}





// Can convert html and 2d arrays into a latex table.
class LatexTable {

	// Class attributes
	private $html = null;
	private $latex = null;
	private $data = null;
	private $rows = null;
	private $cols = null;

	// Constructor
	public function __construct($html = null) {
		if ($html != null) {
			$this->html = $html;
			$this->extract_table_data();
			$this->generate_latex();
		}
	}

	// Setters
	public function set_html($html) {
		$this->html = $html;
	}

	public function set_latex($latex) {
		$this->latex = $latex;
	}

	public function set_data($data) {
		$this->data = $data;
	}

	public function set_rows($rows) {
		$this->rows = $rows;
	}

	public function set_cols($cols) {
		$this->cols = $cols;
	}

	// Getters
	public function get_html() {
		return $this->html;
	}

	public function get_latex() {
		return $this->latex;
	}

	public function get_data() {
		return $this->data;
	}

	public function get_cols() {
		return $this->cols;
	}

	public function get_rows() {
		return $this->rows;
	}

	// Extract data from html table and store in $this->data
	public function extract_table_data() {
		// Check that html is not empty
		if ($this->html == null) {
			error_log("LatexTable: could not extract table data, html variable set to null");
			return;
		}
		// Initialise array
		$this->data = array();
		// Set initial values
		$row = -1;
		$col = -1;
		$td_open = false;
		// Extract tags from html code
		$tags = explode("<", $this->html);
		// Loop over each tag and process
		foreach ($tags as $tag) {
			// Find position of end tag
			$end_tag = strpos($tag, ">");
			// Get the tag name
			$element = substr($tag, 0, $end_tag);
			// Check which tag is used
			switch ($element) {
				// Create new row
				case "tr":
					$td_open = false;
					$col = -1;
					array_push($this->data, array());
					$row++;
					break;
				// Create new cell
				case "td":
					$cell = "";
					$cell = substr($tag, $end_tag + 1, strlen($tag));
					$td_open = true;
					$col++;
					break;
				// End a cell
				case "/td":
					$this->data[$row][$col] = $cell;
					$td_open = false;
					break;
				// End a row
				case "/tr":
					$td_open = false;
				// Do nothing
				case "table":
					continue;
				// Terminate at end of table
				case "/table":
					break;
				// If not a tag and inside a td then add to cell data
				default:
					if ($td_open) {
						$cell .= "<" . $tag;
					}
			}
		}
	}

	// Create latex code from $this->data
	public function generate_latex() {
		// Check there is data
		if ($this->data == null) {
			// echo "Error: Table data is null\n";
			return;
		}
		// Find rows and columns
		$num_rows = count($this->data);
		$num_cols = count($this->data[0]);

		// Create table head
		$latex = "";
		$latex .= "\\scriptsize{";
		$latex .= "\begin{longtabu} to \linewidth {|";
		// Add column settings
		for ($i=0; $i < $num_cols; $i++) {
			$latex .= "X[l]|";
		}
		$latex .= "}\n\\hline\n";
		// add rows of data to table
		foreach ($this->data as $row) {
			// add each cell value to table
			for ($i=0; $i < $num_cols; $i++) {
				// if there is a value in cell then add text
				if (isset($row[$i])) {
					// TODO: Support html characters in the cell data.
					$latex .= html2latex($row[$i], true);
				}
				// Add cell seperator
				if ($i != $num_cols - 1) {
					$latex .= " & ";
				}
			}
			// end row and draw horizontal line
			$latex .= "\\\\\n\\hline\n";
		}
		// End table
		$latex .= "\\end{longtabu}\n";
		$latex .= "}";
		$latex .= "\\normalsize\n";

		// set classes latex to generated latex
		$this->latex =  $latex;
	}

	// Extract number of rows from html
	public function find_num_rows($html) {
		if ($this->html == null) {
			error_log("LatexTable: could not find num rows, html variable set to null");
			return;
		}
		$this->rows = 0;
		$tags = explode("<", $html);
		foreach ($tags as $tag) {
			if (substr($tag, 0, 2) == "tr") {
				$this->rows++;
			} elseif (substr($tag, 0, 3) == "/table") {
				break;
			}
		}
		return $this->rows;
	}

	// Etxract number of cols from html
	public function find_num_cols($html) {
		if ($this->html == null) {
			error_log("LatexTable: could not find num cols, html variable set to null");
			return;
		}
		$this->cols = 0;
		$tags = explode("<", $html);
		foreach ($tags as $tag) {
			if (substr($tag, 0, 2) == "td") {
				$this->cols++;
			} elseif (substr($tag, 0, 3) == "/tr") {
				break;
			}
		}
		return $this->cols;
	}
}
