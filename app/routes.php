<?php

// AJAX routes are typically post requests

// main site routes
require 'routes/login.php';
require 'routes/home.php';
require 'routes/schedule.php';
require 'routes/edit_user.php';
require 'routes/edit_session_dates.php';
require 'routes/statistics.php';

// lg specific routes to pages
require 'routes/lg/list.php';
require 'routes/lg/dash.php';
require 'routes/lg/details.php';
require 'routes/lg/generate.php';
require 'routes/lg/view_pdf.php';
require 'routes/lg/review.php';
require 'routes/lg/merge_old.php';

// deletes a lg and returns to list
require 'routes/lg/delete.php';

// get and puts for editing lg details
// - gets give a json object
// - puts are posts that save data to a lg with an ajax request
require 'routes/lg/get.php';

// Updates lg details (buttons found on lg dash), navigate to page with post
// data and it will navigate back to the dash when complete
require 'routes/lg/set_state.php';
require 'routes/lg/set_dap.php';
require 'routes/lg/set_uc.php';

// Routes for handling email, use with an ajax request
require 'routes/lg/email_functions.php';

// Routes for editing lg page rendering
require 'routes/lg/edit/staff.php';
require 'routes/lg/edit/feedback.php';
require 'routes/lg/edit/outcomes.php';
require 'routes/lg/edit/approaches.php';
require 'routes/lg/edit/clo.php';
require 'routes/lg/edit/assessment_summary.php';
require 'routes/lg/edit/assessments.php';
require 'routes/lg/edit/marking_criteria.php';
require 'routes/lg/edit/activities.php';
require 'routes/lg/edit/readings.php';

require 'routes/test.php';
