<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use VanguardLTE\Games\CedarMath;
if (!in_array(config('database.connections.mysql.host'), ['localhost','127.0.0.1','::1'], true)) throw new RuntimeException('Local only');
$cat=DB::table('categories')->where('href','cedar_games')->first();
if (!$cat) throw new RuntimeException('Category missing');
echo 'CATEGORY '.json_encode($cat).PHP_EOL;
$ids=DB::table('game_categories')->where('category_id',$cat->id)->pluck('game_id');
echo 'GAMES '.DB::table('games')->whereIn('id',$ids)->get(['id','name','view','shop_id'])->toJson().PHP_EOL;
foreach (['dice','wheel','plinko','mines','crash','royal_steps'] as $slug) {
 echo $slug.' settings '.json_encode(['edge'=>max(5,(float)settings('cedar_'.$slug.'_house_edge',5)),'min'=>settings('cedar_'.$slug.'_min_bet',10),'max'=>settings('cedar_'.$slug.'_max_bet',50000)]).PHP_EOL;
}
echo 'PAYOUT CAP '.settings('cedar_max_payout',1000000).' CRASH CAP '.settings('cedar_crash_max_multiplier',1000).PHP_EOL;
foreach(CedarMath::wheel() as $n=>$risks) foreach($risks as $risk=>$table) echo "Wheel $n $risk ".(100*array_sum($table)/$n).PHP_EOL;
for($n=8;$n<=16;$n++) foreach(['low','medium','high'] as $risk) echo "Plinko $n $risk ".(100*CedarMath::plinkoRtp($n,$risk)).PHP_EOL;
// Exhaustive analytic expectation checks, independent of random samples.
$diceMin=1; $diceMax=0;
foreach (['under','over'] as $condition) {
 for($target=($condition==='under'?100:199);$target<=($condition==='under'?9800:9899);$target++) {
  $wins=$condition==='under'?$target:9999-$target;
  $mult=floor(95/($wins/100)*10000)/10000;
  $rtp=$wins/10000*$mult;
  $diceMin=min($diceMin,$rtp);$diceMax=max($diceMax,$rtp);
 }
}
echo 'Dice all allowed targets RTP '.($diceMin*100).' to '.($diceMax*100).PHP_EOL;
$minesMin=1;$minesMax=0;
for($mines=1;$mines<=24;$mines++) {
 $p=1;
 for($safe=1;$safe<=25-$mines;$safe++) {
  $p*=(26-$mines-$safe)/(26-$safe);
  $rtp=$p*CedarMath::minesMultiplier($mines,$safe,5);
  $minesMin=min($minesMin,$rtp);$minesMax=max($minesMax,$rtp);
 }
}
echo 'Mines all 300 stopping points RTP '.($minesMin*100).' to '.($minesMax*100).PHP_EOL;
foreach(CedarMath::STEPS as $i=>$mult) echo 'Royal step '.($i+1).' survival '.(floor(9500/$mult)/100).'% RTP '.(floor(9500/$mult)*$mult/100).'%'.PHP_EOL;
foreach([1.01,2,10,100] as $auto) echo "Crash auto $auto RTP ".(95*$auto/($auto+0.01)).'% (strictly before rounded crash)'.PHP_EOL;
echo 'Cap example: 50000 wager at Royal step 10 pays 1000000, effective RTP '.(0.0095*1000000/50000*100).'%'.PHP_EOL;
