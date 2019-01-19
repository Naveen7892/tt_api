<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// Check home page
Route::get('/', function () {
    //return view();
	return 'Welcome to Table Tennis Tournament!';
});

// ==================== TT - ROUTES ======================

Route::get('/players', function() {
    //$players = App\tt_player::all();
    //echo $players;
    
    $results = DB::select(
        'SELECT player_id, name, mail_id, age, sex, hand, grip, description, style, role FROM tt_players', 
        [ ]
    );
    
    echo json_encode($results, JSON_PRETTY_PRINT);
});

Route::get('/player/{id}', function($id) {
    
    //$player = App\tt_player::where('player_id', '=', $id)->get();
    //print_r($player);
    
    $results = DB::select(
        'SELECT p.player_id, p.name, p.mail_id, p.age, p.sex, p.hand, p.grip, p.description, p.style, p.role, (SELECT COUNT(DISTINCT(im1.tournament_id)) FROM tt_individual_matches im1 WHERE im1.p1 = :id1 or im1.p2 = :id2) AS total_tournaments, (SELECT DISTINCT COUNT(t1.winner) FROM tt_individual_tournaments t1 WHERE t1.winner = :id3) AS tournament_wins, (SELECT COUNT(winner) FROM tt_individual_matches im2 WHERE im2.winner = :id4) AS total_wins, (SELECT (COUNT(*) - total_wins) FROM tt_individual_matches im3 WHERE im3.p1 = :id5 or im3.p2 = :id6) AS total_loses, (SELECT COALESCE(SUM(im4.p1_score), 0) FROM tt_individual_matches im4 WHERE im4.p1 = :id7)+(SELECT COALESCE(SUM(im5.p2_score), 0) FROM tt_individual_matches im5 WHERE im5.p2 = :id8) AS total_scores FROM tt_players p WHERE p.player_id = :id9', 
        [
            'id1' => $id,
            'id2' => $id,
            'id3' => $id,
            'id4' => $id,
            'id5' => $id,
            'id6' => $id,
            'id7' => $id,
            'id8' => $id,
            'id9' => $id,
        ]
    );
    
    echo json_encode($results[0], JSON_PRETTY_PRINT);
    
//    if(empty($player[0])) {
//        echo 'No player with the given ID!';
//    } else {
//        echo $player[0]->name;    
//    }
});

Route::get('/matches_all', function() {
   $results = DB::select(
       'SELECT im.tournament_id, im.im_id AS match_id, p1.name AS player_1, p2.name AS player_2, s.name AS serviced_by, im.p1_score, im.p2_score, im.duration_minutes AS duration, w.name AS winner FROM tt_individual_matches im JOIN tt_players p1 ON im.p1 = p1.player_id JOIN tt_players p2 ON im.p2 = p2.player_id JOIN tt_players s ON im.serviced_by = s.player_id JOIN tt_players w ON im.winner = w.player_id ORDER BY im.im_date ASC',
       [ ]
   ); 
    
   echo json_encode($results, JSON_PRETTY_PRINT);
});

Route::get('/tournament/{id}', function($id) {
   $results = DB::select(
       'SELECT im.tournament_id, im.im_id, im.im_date, p1.name, p2.name, s.name, im.p1_score, im.p2_score, im.duration_minutes, w.name  FROM tt_individual_matches im JOIN tt_players p1 ON im.p1 = p1.player_id JOIN tt_players p2 ON im.p2 = p2.player_id JOIN tt_players s ON im.serviced_by = s.player_id JOIN tt_players w ON im.winner = w.player_id WHERE im.tournament_id = :tournament_id ORDER BY im.im_date ASC',
       [ 'tournament_id' => $id ]
   ); 
    
   echo json_encode($results, JSON_PRETTY_PRINT);
});

Route::get('/player_rank', function() {
   $results = DB::select(
       'SELECT im1.tournament_id, p.player_id, p.name, COALESCE((SELECT COUNT(*) FROM tt_individual_matches im5 WHERE im5.p1 = p.player_id or im5.p2 = p.player_id), 0) AS matches, COALESCE((SELECT COUNT(im2.winner) FROM tt_individual_matches im2 WHERE im2.winner = p.player_id GROUP BY im2.winner), 0) AS wins, (SELECT (COUNT(*) - wins) FROM tt_individual_matches im6 WHERE im6.p1 = p.player_id or im6.p2 = p.player_id) AS loses, (SELECT COALESCE(SUM(im3.p1_score), 0) FROM tt_individual_matches im3 WHERE im3.p1 = p.player_id)+(SELECT COALESCE(SUM(im4.p2_score), 0) FROM tt_individual_matches im4 WHERE im4.p2 = p.player_id) AS total_scores, COALESCE((SELECT COUNT(im2.winner)*2 FROM tt_individual_matches im2 WHERE im2.winner = p.player_id GROUP BY im2.winner), 0) AS points FROM tt_individual_matches im1 JOIN tt_players p ON p.player_id = im1.p1 or p.player_id = im1.p2 GROUP BY p.player_id, im1.tournament_id, p.name ORDER BY wins DESC',
       [ ]
   ); 
    
   echo json_encode($results, JSON_PRETTY_PRINT);
});

Route::post('/player/add', function(Request $request) {
    $inputs = $request->all();
    // Validate input data
    if(empty($inputs['name'])) {
        $inputs['name'] = NULL;
    }
    if(empty($inputs['mail_id'])) {
        $inputs['mail_id'] = NULL;
    }
    if(empty($inputs['age'])) {
        $inputs['age'] = 25;
    }
    if(empty($inputs['sex'])) {
        $inputs['sex'] = 'M';
    }
    if(empty($inputs['hand'])) {
        $inputs['hand'] = 'RIGHT';
    }
    if(empty($inputs['grip'])) {
        $inputs['grip'] = 'SHAKEHAND';
    }
    if(empty($inputs['style'])) {
        $inputs['style'] = 'DRIVE';
    }
    if(empty($inputs['description'])) {
        $inputs['description'] = '';
    }
    if(empty($inputs['role'])) {
        $inputs['role'] = 0;
    }
       
    // Insert into DB
    $results = DB::insert(
       'INSERT INTO tt_players(name, mail_id, age, sex, hand, grip, style, description, role) VALUES(:name, :mail_id, :age, :sex, :hand, :grip, :style, :description, :role)',
       [ 
           'name' => $inputs['name'],
           'mail_id' => $inputs['mail_id'],
           'age' => $inputs['age'],
           'sex' => $inputs['sex'],
           'hand' => $inputs['hand'],
           'grip' => $inputs['grip'],
           'style' => $inputs['style'],
           'description' => $inputs['description'],
           'role' => $inputs['role']
       ]
   ); 
    
   echo json_encode($results, JSON_PRETTY_PRINT);
});

Route::post('/matches/add', function(Request $request) {
    $inputs = $request->all();
    // Validate input data
//    if(empty($inputs['name'])) {
//        $inputs['name'] = NULL;
//    }
//    if(empty($inputs['mail_id'])) {
//        $inputs['mail_id'] = NULL;
//    }
//    if(empty($inputs['age'])) {
//        $inputs['age'] = 25;
//    }
//    if(empty($inputs['sex'])) {
//        $inputs['sex'] = 'M';
//    }
//    if(empty($inputs['hand'])) {
//        $inputs['hand'] = 'RIGHT';
//    }
//    if(empty($inputs['grip'])) {
//        $inputs['grip'] = 'SHAKEHAND';
//    }
//    if(empty($inputs['style'])) {
//        $inputs['style'] = 'DRIVE';
//    }
//    if(empty($inputs['description'])) {
//        $inputs['description'] = '';
//    }
//    if(empty($inputs['role'])) {
//        $inputs['role'] = 0;
//    }
       
    // Insert into DB
    $results = DB::insert(
       'INSERT INTO tt_individual_matches(tournament_id, p1, p2, p1_score, p2_score, serviced_by, level, duration_minutes, winner, im_date) VALUES(:tournament_id, :p1, :p2, :p1_score, :p2_score, :serviced_by, :level, :duration_minutes, :winner, :im_date)',
       [ 
           'tournament_id' => $inputs['tournament_id'],
           'p1' => $inputs['p1'],
           'p2' => $inputs['p2'],
           'p1_score' => $inputs['p1_score'],
           'p2_score' => $inputs['p2_score'],
           'serviced_by' => $inputs['serviced_by'],
           'level' => $inputs['level'],
           'duration_minutes' => $inputs['duration_minutes'],
           'winner' => $inputs['winner'],
           'im_date' => $inputs['im_date']
       ]
   ); 
    
   echo json_encode($results, JSON_PRETTY_PRINT);
});

Route::post('/tournament/add', function(Request $request) {
    $inputs = $request->all();
    // Validate input data
    if(empty($inputs['tournament_id'])) {
        $inputs['tournament_id'] = '2016NOV-1';
    }
    if(empty($inputs['winner'])) {
        $inputs['winner'] = NULL;
    }
    if(empty($inputs['total_matches'])) {
        $inputs['total_matches'] = 0;
    }
    if(empty($inputs['tournament_ended'])) {
        $inputs['tournament_ended'] = 0;
    }
    // Insert into DB
    $results = DB::insert(
       'INSERT INTO tt_individual_tournaments(tournament_id, winner, total_matches, tournament_ended) VALUES(:tournament_id, :winner, :total_matches, :tournament_ended);',
       [ 
           'tournament_id' => $inputs['tournament_id'],
           'winner' => $inputs['winner'],
           'total_matches' => $inputs['total_matches'],
           'tournament_ended' => $inputs['tournament_ended']
       ]
   ); 
    
   echo json_encode($results, JSON_PRETTY_PRINT);
});