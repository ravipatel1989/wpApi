<?php
$themeuri = get_stylesheet_directory();
require_once $themeuri . '/library/TCPDF/examples/tcpdf_include.php';

// Extend the TCPDF class to create custom Header and Footer
class CUSTOMPDF extends TCPDF {

    //Page header
    public function Header() {
		// Set font
        $this->SetFont('helvetica', '', 8);
		$this->SetY(5);
		$this->Cell(0, 15, 'Name: '.$this->CustomHeaderText, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->Cell(0, 15, 'My Pathway Plan', 0, false, 'R', 0, '', 0, false, 'M', 'M');
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
		$this->SetFont('helvetica', '', 8);
        $this->Cell(0, 15, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

class generate_pdf {
    public static function my_pathway_plan_fn($pathwayData = false)
    {
        $pdf = new CUSTOMPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetFont('dejavusans', '', 10);
		//$pdf->SetPrintHeader(false); // To Remove Header
		//$pdf->SetPrintFooter(false); // To Remove Footer
		$pdf->CustomHeaderText = $pathwayData->first_name." ".$pathwayData->last_name;
		$pdf->AddPage();
		//$pdf->AddPage('P','A3'); //Output Will be on A3 Size
		$tagvs = array('h1' => array(0 => array('h' => 1, 'n' => 3), 1 => array('h' => 1, 'n' => 2)),
               'h2' => array(0 => array('h' => 1, 'n' => 2), 1 => array('h' => 1, 'n' => 1)));
		$pdf->setHtmlVSpace($tagvs);
		
        $html = <<<EOD
                <style>
                h1 {
                    color: #0073b2;
                    font-size: 24pt;
                }
                h2{
                    background-color:#0073b2;
                    color:#fff;
                }
                p{
                    color: #0073b2;
                    font-size:10px;
                }
                </style>
                <h1>My Pathway Plan</h1>
                <h2> Preface</h2>
                <p>(1) Regulations require that the pathway plan must be prepared as soon as possible after the
                needs assessment and must include the care plan. The needs assessment must be completed not
                more than 3 months after the date on which the young person reaches the age of 16 or becomes
                an eligible child after that age; within 3 months of arrival if they are an unaccompanied asylum
                seeker; within 3 months of becoming relevant if they do not already have a pathway plan; within
                3 months of the LA being informed that a former relevant young person is pursuing, or wishes to
                pursue, a programme of education or training</p>
                <p>(2) The pathway plan must set out how the young person's needs are to be met and the date by
                which, and by whom, any action required to implement any aspect of the plan will be carried out.</p>
                <p>(3) The pathway plan must be reviewed within the statutory regulations and when significant
                change impacts upon the plan. For example, a review may be called by the young person or a PA
                or other professional when there is an assessed risk of crisis or a change in circumstances (e.g.
                planned move, homelessness, sentenced to custody, or becoming a parent). The results of the
                review and any changes to the pathway plan must be recorded in writing.</p>
                <p>(4) This document incorporates the Needs Assessment, Care / Pathway Plan and Review.
                Practitioners should consider in their assessment, planning and review: the needs of the young
                person, what progress has been made since the last review (or since the Plan was drawn up if
                this is the first Review). Have the young person's needs changed? What outcomes have been
                achieved? What aspects of the Plan still need to be delivered? Does the Plan or the contingency
                arrangements need to be revised.</p>
EOD;
        // output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->AddPage();
		//$pdf->AddPage('P','A3');
        $html = <<<EOD
                <style>
                h2{
                    background-color:#0073b2;
                    color:#fff;
                }
                p{
                    margin:0;
                    padding:0;
                }
                p, h3, table{
                    color: #0073b2;
                    font-size:12px;
                }
                table#small th{ font-size:9px; }
                th{ text-align: left;}
                td, span {border:1px solid #C0C0C0; color:#000; font-size:10px;}
                h3{ text-decoration: underline; }
                </style>
                <h2> All about me</h2>
                <p>This section is to record an overview of all your personal details</p>
                <h3>All about me</h3>
                <table cellspacing="5" cellpadding="3">
                <tr><th>Name</th><th>Date of birth</th><th>Gender</th></tr>
                <tr><td>{$pathwayData->first_name} {$pathwayData->last_name}</td><td>{$pathwayData->dob}</td><td>{$pathwayData->gender}</td></tr>
                </table>
                <table cellspacing="5" cellpadding="3">
				<tr><th rowspan="2"><span>{$pathwayData->disabled}</span> Disabled</th><th colspan="2">Address</th></tr>
                <tr><td colspan="2">Address</td></tr>
                </table>
                <table cellspacing="5" cellpadding="3">
                <tr><th>Communication needs</th><th>Legal status</th></tr>
                <tr><td>{$pathwayData->communication_needs}</td><td>{$pathwayData->legal_status}</td></tr>
                </table>
                <p>For young people who have both a leaving care and immigration status, please click on the guidance note for more information.</p>
                <table cellspacing="5" cellpadding="3">
                <tr><th>Leaving care status</th><th colspan="2">Immigration status</th></tr>
                <tr><td>{$pathwayData->leaving_care_status}</td><td colspan="2">{$pathwayData->Immigration_status}</td></tr>
                </table>
                <p>If you do not have the following documentation, it is important to make sure that the person's details who does have this information are included in the section below.</p>
                <table cellspacing="5" cellpadding="3">
				<tr><th>Who has got my Birth Certificate</th></tr>
                <tr><td>{$pathwayData->who_has_got_my_birth_certificate}</td></tr>
				</table>
                <table cellspacing="5" cellpadding="3">
                <tr><th>Who has got my Passport</th><th>Where is my NI number recorded</th></tr>
                <tr><td>{$pathwayData->who_has_got_my_passport}</td><td>{$pathwayData->ni_number}</td></tr>
                </table>
                <h3>Plan Date</h3>
                <table id="small" cellspacing="5" cellpadding="3">
                <tr><th>Date of this Pathway Plan / review</th><th>Date of next Pathway Plan Review</th><th>Name of Social Worker / PA</th></tr>
                <tr><td>{$pathwayData->created_date}</td><td>{$pathwayData->due_date}</td><td>{$pathwayData->createdBy}</td></tr>
                </table>
EOD;
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->AddPage();
		//$pdf->AddPage('P','A3');
        $html = <<<EOD
                <style>
                h2{
                    background-color:#0073b2;
                    color:#fff;
                }
                p{
                    margin:0;
                    padding:0;
                }
                p, h3, table{
                    color: #0073b2;
                    font-size:12px;
                }
                h4{
                    color: #0073b2;
                }
                table#people tr th{
                    background-color:#0073b2; 
                    text-align:center; 
                    color:#FFF;
                }
				td{border:1px solid #C0C0C0; color:#000; font-size:10px;}
                </style>
                <h4>People involved in preparing the needs assessment, developing this plan and review</h4>
                <table id="people" border="1">
                <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Telephone number(s)</th>
                <th>Invited</th>
                <th>Attended</th>
                <th>Contributed</th>
                <th>Emergency contact</th>
                </tr>
EOD;
			if($pathwayData->supporting_people && is_array($pathwayData->supporting_people)){
            foreach($pathwayData->supporting_people as $supporting_people){
$html .= <<<EOD
				<tr>
                <td>{$supporting_people['first_name']} {$supporting_people['last_name']}</td>
                <td>{$supporting_people['type']}</td>
                <td>{$supporting_people['phone_number']}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
				</tr>
EOD;
			}
            }
			$html .= <<<EOD
                </table>
                <h2>Overall Plan</h2>
				<table cellspacing="5" cellpadding="3">
                <tr><th>What is the overall Care Plan for this young person</th></tr>
                <tr><td>{$pathwayData->overall_care_plan}</td></tr>
				</table>
                <table cellspacing="5" cellpadding="3">
                <tr><th>What attempts have been made to arrange for the young person to live with a relative or a close family friend as an alternative to care or accommodation if the child / young person is not already in a family or friends placement</th></tr>
                <tr><td>{$pathwayData->attempts}</td></tr>
				</table>
                
                <h4>My family and social relationships</h4>
                <p>If you are looked after then contact between you and your family are likely to continue as planned, please discuss what arrangements you would like to make with your worker and carer. Please give details on how often, how, dates, times and locations of contact with friends or relatives including transport to and from.</p>
				<table cellspacing="5" cellpadding="3">
                <tr><th>My family and social relationships</th></tr>
                <tr><td>{$pathwayData->family_relationship}</td></tr>
				</table>
				<table cellspacing="5" cellpadding="3">
                <tr><th>Worker’s assessment</th></tr>
                <tr><td>{$pathwayData->workers_assessment}</td></tr>
				</table>
                <table cellspacing="5" cellpadding="3">
                <tr><th>Contact Arrangements</th></tr>
                <tr><td>{$pathwayData->contact_arrangements}</td></tr>
				</table>

EOD;
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->AddPage();
		//$pdf->AddPage('P','A3');
        $html = <<<EOD
                <style>
                h2{
                    background-color:#0073b2;
                    color:#fff;
                }
                p{
                    margin:0;
                    padding:0;
                }
                p, h3, table{
                    color: #0073b2;
                    font-size:12px;
                }
                h4{
                    color: #0073b2;
                }
                table#people tr th{
                    background-color:#0073b2; 
                    text-align:center; 
                    color:#FFF;
                }
				td{border:1px solid #C0C0C0; color:#000; font-size:10px;}
                </style>
                <h2>Visits and significant events</h2>
                <h4>Dates young person seen</h4>
                <table id="people" border="1">
                <tr>
                <th width="10%">Date</th>
                <th width="10%">Was the young person seen alone?</th>
                <th width="80%">Telephone number(s)</th>
                </tr>
                <tr>
                <td>{$pathwayData->date_of_visiting}</td>
                <td>{$pathwayData->seen_alone}</td>
                <td></td>
                </tr>
                </table>
                
                <table cellspacing="5" cellpadding="3">
                <tr>
                <th width="25%" rowspan="2">If these visits are outside statutory timescales, or the child was not seen alone, please explain why?</th>
                <td width="70%">{$pathwayData->outside_statutory}</td>
                </tr>
				<tr><th></th></tr>
                </table>
                <h4>Dates and details of other significant visits and meetings with family and / or professionals</h4>
                <table id="people" border="1">
                <tr>
                <th width="40%">Visit / meeting details</th>
                <th width="25%">Name of family member involved in the meeting</th>
                <th width="25%">Involved professional's name</th>
                <th width="10%">Date of visit / meeting</th>
                </tr>
EOD;
            $last_visite_brief = '';
			if($pathwayData->visits && is_array($pathwayData->visits)){
            foreach($pathwayData->visits as $visit){
				$last_visite_brief = $visit->update_visit;
$html .= <<<EOD
				<tr>
                <td>{$visit->visit}</td>
                <td>{$visit->member_name}</td>
				<td>{$visit->professionals_name}</td>
				<td>{$visit->date}</td>
				</tr>
EOD;
			}
            }
			$html .= <<<EOD
                </table>
                <p></p>
                <table cellspacing="5" cellpadding="3">
                <tr>
                <th width="25%" rowspan="2">Significant events - Brief update since last visit</th>
                <td width="70%">{$last_visite_brief}</td>
                </tr>
                </table>
EOD;
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->AddPage();
		//$pdf->AddPage('P','A3');
        $html = <<<EOD
                <style>
                h2{
                    background-color:#0073b2;
                    color:#fff;
                }
                p{
                    margin:0;
                    padding:0;
                }
                p, h3, table{
                    color: #0073b2;
                    font-size:12px;
                    text-align:bottom;
                }
                ul{ list-style-position: inside; }
                h4, ul li{
                    color: #0073b2;
                }
                table#people tr th{
                    background-color:#0073b2; 
                    text-align:center; 
                    color:#FFF;
                }
				td{border:1px solid #C0C0C0; color:#000; font-size:10px;}
                </style>
                <h2>People supporting me</h2>
                <br>
                <h4>People supporting me</h4>
				<table cellspacing="5" cellpadding="3">
                <tr><th>What support services are available outside of office hours, who can be contacted and how?</th></tr>
                <tr><td></td></tr>
                </table>

                <h2>My education, employment and training</h2>
                <p>Your education is very important, the more you invest now the more opportunities will open for you, you have support in place to assist you,
                and your participation is essential. If you are in full or part-time education or training then you may continue to have a PEP to accompany your
                Pathway Plan. The Pathway Plan will focus particularly on your career plans and any help you need to achieve them. Questions you might like
                to consider are: What do you want to achieve? What job do you want to do? What education or training or work experience do you need to get
                the job that you want? What application forms do you need to complete? How will your education or training be financed? What support do you
                need?</p>
                <h3>Education, employment and training history</h3>
                <table id="people" border="1">
                <tr>
                <th width="18%">Current establishment</th>
                <th width="18%">Address</th>
                <th width="18%">Telephone number</th>
                <th width="18%">Support contact</th>
                <th width="10%">Date</th>
                <th width="18%">Responsible LA</th>
                </tr>
                <tr>
                <td>{$pathwayData->education_current_establishment}</td>
                <td>{$pathwayData->education_address}</td>
                <td>{$pathwayData->education_phone}</td>
                <td>{$pathwayData->education_support_contact}</td>
                <td>{$pathwayData->education_date}</td>
				<td>{$pathwayData->education_responsible_la}</td>
                </tr>
                </table>
                <h3>Education, employment and training</h3>
                
				<table cellspacing="5" cellpadding="3">
                <tr><th>My view</th></tr>
                <tr><td>{$pathwayData->education_feeling}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>What is working well?</th></tr>
                <tr><td>{$pathwayData-> education_working_well}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>What are we worried about</th></tr>
                <tr><td>{$pathwayData->education_worried_about}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>My education plan: long term goals</th></tr>
                <tr><td>{$pathwayData->education_current_establishment}</td></tr>
                </table>

                <h3>What needs to happen?</h3>
				<table cellspacing="5" cellpadding="3">
                <tr><th>Next steps</th></tr>
                <tr><td>{$pathwayData->education_next_steps}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Long term goals</th></tr>
                <tr><td>{$pathwayData->education_long_term_goals}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Contingency if any wrong</th></tr>
                <tr><td>{$pathwayData->education_contingency}</td></tr>
                </table>
                <h2>My health and development</h2>
                <p>This section is about your health. This includes your self-esteem, self-awareness, stress management, self-control and confidence as well as your physical, emotional and mental health needs. It is also important for the right people to know about any allergies, current medication
                or treatment you have. Your PA / worker may also ask you whether you would like any help with diet, fitness, immunisations, sexual health, smoking, drugs, alcohol etc. They will discuss with you how you access healthcare services (doctors, dentists, specialist services etc) and any
                special equipment you need, and whether you know your medical history or would like help to find out about it.
                Do you know how to find out about your medical history, if not would you like help in this?
                Would you like advice and information with: diet, fitness, sexual health, self-harm, smoking, drugs, alcohol, mental health, emotional health,
                sexuality?</p>
                <h3>Health and development</h3>
                
				<table cellspacing="5" cellpadding="3">
                <tr><th>What's working well</th></tr>
                <tr><td>{$pathwayData->health_working_well}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>What are we worried about?</th></tr>
                <tr><td>{$pathwayData->health_worried_about}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Known allergies and / or medical conditions</th></tr>
                <tr><td>{$pathwayData->health_allergies}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Think about Mental Health and Wellbeing</th></tr>
                <tr><td>{$pathwayData->mental_health}</td></tr>
                </table>

                <h3>What needs to happen?</h3>
				<table cellspacing="5" cellpadding="3">
                <tr><th>Next steps?</th></tr>
                <tr><td>{$pathwayData->health_next_steps}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Long term goals</th></tr>
                <tr><td>{$pathwayData->health_long_term_goals}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Contingency</th></tr>
                <tr><td>{$pathwayData->health_contingency}</td></tr>
                </table>
				
                <h2>Managing and living independently</h2>
				<table cellspacing="5" cellpadding="3">
                <tr><th>Long term goals</th></tr>
                <tr><td>{$pathwayData->education_long_term_goals}</td></tr>
                </table>

                <h3>Where i live / Housing needs</h3>
				<table cellspacing="5" cellpadding="3">
                <tr><th>What's working well</th></tr>
                <tr><td>{$pathwayData->managing_working_well}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>What are we worried about</th></tr>
                <tr><td>{$pathwayData->managing_worried_about}</td></tr>
                </table>

                <h3>What needs to happen?</h3>
				<table cellspacing="5" cellpadding="3">
                <tr><th>Next steps?</th></tr>
                <tr><td>{$pathwayData->managing_next_steps}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Long term Goals</th></tr>
                <tr><td>{$pathwayData->managing_long_term_goals}</td></tr>
                </table>
 
				<table cellspacing="5" cellpadding="3">
                <tr><th>Contingency</th></tr>
                <tr><td>{$pathwayData->managing_contingency}</td></tr>
                </table>
				
                <h2>My money</h2>
                <p>Are you entitled to claim any grants, student finance or any other benefits (do you need help with any of these)? Can you budget your money for travel, clothes, food, savings etc.</p>
                <h3>Money</h3>
                <p>In order to live independently you need to be able to manage your money by saving regularly and budgeting for your needs (food, clothes, accommodation, travel etc). Do you have a bank account, national insurance number, regular income, savings or debts? Are you entitled to claim student finance or other benefits? Do you need any advice or guidance on managing your money? Do you know what to do if your finances change or the cost of your accommodation rises?</p>
                <table>
                <tr>
                <th width="25%">Whats working well</th>
                <td width="75%">{$pathwayData->money_working_well}</td>
                </tr>
                <tr>
                <th width="25%">What are we worried about</th>
                <td width="75%">{$pathwayData->money_worried_about}</td>
                </tr>
                </table>
                <h3>What needs to happen?</h3>
				<table cellspacing="5" cellpadding="3">
                <tr><th>Next steps?</th></tr>
                <tr><td>{$pathwayData->money_next_steps}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Long term Goals</th></tr>
                <tr><td>{$pathwayData->money_long_term_goals}</td></tr>
                </table>

				<table cellspacing="5" cellpadding="3">
                <tr><th>Contingency</th></tr>
                <tr><td>{$pathwayData->money_contingency}</td></tr>
                </table>
                <h2>Guidance for My Pathway Plan</h2>
				

                <h3>Volume 3: Planning Transition to Adulthood for Care Leavers</h3>
                <p>(Figure 1 - Needs assessment and content of pathway plans for relevant and former relevant children.)</p>
                <p></p><p></p><p></p>
                <h3>1. Health and development</h3>
                <ul>
                <li>Use of primary healthcare services.</li>
                <li>Arrangements for the young person's medical and dental care according to their needs making reference to the health plan established within the care plan in place when the young person was looked after.</li>
                <li>Access to specialist health and therapeutic services.</li>
                <li>Arrangements so that young person understands the actions they can take to maintain a healthy lifestyle.</li>
                <li>Opportunities to enjoy and achieve and take part in positive leisure activities.</li>
                </ul>
                <h3>2. Education, training and employment</h3>
                <ul>
                <li>Statement of the young person's aspirations and career ambitions and actions and support to achieve this.</li>
                <li>Access to careers advice.</li>
                <li>Education objectives and support - continue to use the young person's Personal Education Plan.</li>
                <li>Arrangements to support the young person in further education and/or higher education.</li>
                <li>Support to enable suitably qualified young people to enter apprenticeships, make applications to university or gain necessary qualifications.</li>
                <li>Arrangements for work experience, career mentoring or pathways into employment etc.</li>
                </ul>
                <h3>Emotional and behavioural development</h3>
                <ul>
                <li>How the authority will assist the young person to develop self esteem and maintain positive attachments.</li>
                <li>Does the young person display self esteem, resilience and confidence?</li>
                <li>Assessment of their capacity to empathise with others, reason and take appropriate responsibility for their own actions.</li>
                <li>Capacity to make attachments and appropriate relationships; show appropriate emotion; adapt to change; manage stress; and show self control and appropriate self awareness.</li>
                </ul>
                <h3>Identity</h3>
                <ul>
                <li>How the authority intends to meet any of the young person's needs arising from their ethnicity, religious persuasion, sexual orientation.</li>
                <li>How does the young person understand their identity stemming from being a child in care and a care leaver?</li>
                <li>How the authority will assist the young person to obtain key documents linked to confirming their age and identity.</li>
                </ul>
                <h3>Family and social relationships</h3>
                <ul>
                <li>Assessment of the young person's relationship with their parents and wider family.</li>
                <li>Contact with family - carried across from care plan.</li>
                <li>Young person's relationship with peers, friendship network and significant adults. Strategy to improve any negative features of these relationships.</li>
                <li>How all these relationships will contribute to the young person making a successful transition to adulthood and how they will assist with integration into the community that they identify with.</li>
                </ul>
                <h3>Practical and other skills necessary for independent living</h3>
                <ul>
                <li>The young person is adequately prepared with the full range of practical skills they will need to manage the next planned move towards greater independence.</li>
                <li>The young person is prepared for taking greater responsibility as they are expected to manage more independently.</li>
                </ul>
                <h3>Financial arrangements</h3>
                <ul>
                <li>Assessment of care leaver's financial needs and their financial capability. Does the young person have a bank account, national insurance number, and appreciate the value of regular saving etc. Do they have access to financial support and adequate income to meet necessary expenses?</li>
                <li>Pathway plan must include a statement of how the authority proposes to maintain a relevant child, the arrangements in place for the young person to receive financial support and contingency plans.</li>
                </ul>
                <h3>(Suitability of) Accommodation</h3>
                <ul>
                <li>An assessment of the quality of accommodation where the young person is living / any accommodation under consideration for them to live in.</li>
                <li>How far is this suitable to the full range of the young person's needs?</li>
                <li>What steps might need to be taken to improve it? (Schedule 2 of the Care Leavers egulations)</li>
                </ul>
                <p>For further information please visit the DFE website - https://www.education.gov.uk</p>
EOD;
        $pdf->writeHTML($html, true, false, true, false, '');
        if (!file_exists(PDF_PATH)) {
            mkdir(PDF_PATH);
        }
		$fileName = PDF_PATH . $pathwayData->pathway_pdf_name. ".pdf";
        $fileUrl = PDF_URL . $pathwayData->pathway_pdf_name. ".pdf";
        //$pdf->Output($fileName, 'I'); //Return the PDF
        $pdf->Output($fileName, 'F'); // Return PDF Link 

        $size = filesize($fileName);
        $unit = false;
        if( (!$unit && $size >= 1<<30) || $unit == "GB")
            $size = number_format($size/(1<<30),2)."GB";
        elseif( (!$unit && $size >= 1<<20) || $unit == "MB")
            $size = number_format($size/(1<<20),2)."MB";
        elseif( (!$unit && $size >= 1<<10) || $unit == "KB")
            $size = number_format($size/(1<<10),2)."KB";
        else
            $size = number_format($size)." B";

        return ['filename' => $pathwayData->pathway_pdf_name, "fileurl" => $fileUrl, "size" => $size];
    }
}
