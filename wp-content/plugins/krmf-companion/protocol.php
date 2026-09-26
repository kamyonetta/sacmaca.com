<?php
// Pure protocol functions: no WordPress, SQL, filesystem or network side effects.
function krmf_tables() {
    return [
        'day_planner_lines'=>[['plan_date','section','position'],['plan_date','section','position','text','checked']],
        'daily_notes'=>[['plan_date'],['plan_date','body']],
        'weekly_planner_tasks'=>[['id'],['id','week_start','title','checked','created_at']],
        'weekly_planner_days'=>[['plan_date'],['plan_date','body','imported_positions']],
        'weekly_planner_notes'=>[['week_start'],['week_start','body']],
        'habits'=>[['id'],['id','title','created_at','weekday_mask','starts_on']],
        'habit_checkins'=>[['habit_id','checkin_date'],['habit_id','checkin_date']],
        'important_events'=>[['id'],['id','event_date','title','color','created_at']],
        'calendar_events'=>[['id'],['id','title','notes','starts_at','ends_at','timezone','all_day','created_at']],
        'timed_event_series'=>[['id'],['id','title','first_date','start_minute','end_minute','repeat_until','color','source_date','source_first','source_last','source_text','source_event_id','excluded_dates']],
    ];
}
function krmf_require($ok, $message='Invalid record') { if (!$ok) throw new InvalidArgumentException($message); }
function krmf_date($s, $min='2026-09-01', $max='2027-12-31') {
    if (!is_string($s) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $s) || $s < $min || $s > $max) return false;
    [$y,$m,$d] = array_map('intval', explode('-', $s)); return checkdate($m,$d,$y);
}
function krmf_validate_request($payload) {
    krmf_require(is_array($payload) && ($payload['protocol'] ?? null) === 1 && is_array($payload['changes'] ?? null) && array_is_list($payload['changes']) && count($payload['changes']) <= 100, 'Invalid protocol or batch');
    foreach ($payload['changes'] as $m) {
        krmf_require(is_array($m) && isset($m['table'],$m['key'],$m['id'],$m['mutation'],$m['base']) && array_key_exists('data',$m));
        $tables = krmf_tables(); $table = $m['table'];
        krmf_require(is_string($table) && isset($tables[$table]));
        [$keys,$columns] = $tables[$table];
        krmf_require(is_array($m['key']) && array_is_list($m['key']) && count($m['key']) === count($keys));
        foreach ($m['key'] as $key) krmf_require((is_string($key) && strlen($key)>0 && strlen($key)<=128) || (is_int($key) && abs($key)<=9007199254740991));
        krmf_require($m['id'] === json_encode(array_merge([$table],$m['key']), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        krmf_require(is_string($m['mutation']) && preg_match('/^[a-zA-Z0-9-]{16,80}$/D',$m['mutation']) && is_int($m['base']) && $m['base'] >= 0);
        $r = $m['data']; if ($r === null) continue;
        krmf_require(is_array($r) && !array_is_list($r) && !array_diff(array_keys($r),$columns));
        foreach ($columns as $column) if (!str_starts_with($column,'source_')) krmf_require(array_key_exists($column,$r),'Incomplete record');
        foreach ($keys as $i=>$key) krmf_require(isset($r[$key]) && $r[$key] === $m['key'][$i]);
        foreach ($r as $v) krmf_require($v===null || is_int($v) || (is_string($v) && strlen($v)<=131072));
        foreach (['plan_date','event_date','first_date','starts_on','checkin_date'] as $field) if (isset($r[$field])) krmf_require(krmf_date($r[$field]));
        if (isset($r['week_start'])) krmf_require(krmf_date($r['week_start'],'2026-08-31','2027-12-27') && date('N',strtotime($r['week_start'].' UTC'))==='1');
        foreach (['checked','all_day'] as $field) if (isset($r[$field])) krmf_require(in_array($r[$field],[0,1],true));
        if ($table==='day_planner_lines') {
            krmf_require(isset($r['text'],$r['checked']) && is_string($r['text']) && mb_strlen($r['text'])<=20 && is_int($r['position']));
            $s=$r['section']; $p=$r['position'];
            krmf_require(($s==='slot' && $p>=-16 && $p<=31 && $r['checked']===0) || ($s==='todo' && $p>=0) || ($s==='note' && $p>=0 && $p<=7 && $r['checked']===0));
        }
        if (in_array($table,['daily_notes','weekly_planner_notes','weekly_planner_days'],true)) krmf_require(isset($r['body']) && is_string($r['body']));
        if ($table==='weekly_planner_tasks') krmf_require(isset($r['week_start'],$r['title'],$r['checked'],$r['created_at']) && is_string($r['title']));
        if ($table==='habits') krmf_require(isset($r['title'],$r['weekday_mask'],$r['starts_on'],$r['created_at']) && is_string($r['title']) && strlen(trim($r['title']))>0 && is_int($r['weekday_mask']) && $r['weekday_mask']>=1 && $r['weekday_mask']<=127);
        if ($table==='weekly_planner_days') krmf_require(isset($r['imported_positions']) && is_string($r['imported_positions']) && is_array(json_decode($r['imported_positions'],true)) && array_is_list(json_decode($r['imported_positions'],true)));
        if (in_array($table,['important_events','timed_event_series'],true)) krmf_require(isset($r['color'],$r['title']) && in_array($r['color'],['red','purple','green','blue','yellow'],true) && is_string($r['title']) && mb_strlen(trim($r['title']))>=1 && mb_strlen($r['title'])<=($table==='important_events'?120:20));
        if ($table==='timed_event_series') {
            krmf_require(isset($r['first_date'],$r['start_minute'],$r['end_minute']) && is_int($r['start_minute']) && is_int($r['end_minute']) && $r['start_minute']>=0 && $r['end_minute']<=1440 && $r['end_minute']>$r['start_minute']);
            if (isset($r['repeat_until'])) krmf_require(krmf_date($r['repeat_until']) && $r['repeat_until'] >= $r['first_date']);
            if (isset($r['excluded_dates'])) krmf_require(is_string($r['excluded_dates']) && is_array(json_decode($r['excluded_dates'],true)) && array_is_list(json_decode($r['excluded_dates'],true)));
        }
        if ($table==='calendar_events') {
            krmf_require(isset($r['title'],$r['starts_at'],$r['ends_at'],$r['all_day'],$r['timezone'],$r['notes'],$r['created_at']) && is_string($r['title']) && strlen(trim($r['title']))>0 && is_string($r['starts_at']) && is_string($r['ends_at']) && $r['ends_at']>$r['starts_at']);
            foreach (['starts_at','ends_at'] as $field) krmf_require($r['all_day']===1 ? krmf_date($r[$field],'1900-01-01','2200-12-31') : (bool)preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?Z$/D',$r[$field]));
        }
    }
}
function krmf_exchange(&$state, $payload) {
    $results=[];
    foreach ($payload['changes'] as $m) {
        if (isset($state['receipts'][$m['mutation']])) {
            $receipt=$state['receipts'][$m['mutation']];
            krmf_require($receipt['request']===$m, 'Mutation ID reused with different data');
            $results[]=$receipt['result']; continue;
        }
        $current=$state['records'][$m['id']] ?? ['id'=>$m['id'],'table'=>$m['table'],'key'=>$m['key'],'data'=>null,'version'=>0];
        if ($current['version'] !== $m['base']) { $results[]=['mutation'=>$m['mutation'],'status'=>'conflict','record'=>$current]; continue; }
        $next=['id'=>$m['id'],'table'=>$m['table'],'key'=>$m['key'],'data'=>$m['data'],'version'=>$current['version']+1];
        $result=['mutation'=>$m['mutation'],'status'=>'accepted','record'=>$next];
        $state['records'][$m['id']]=$next;
        // Receipts are also immutable revision history, including deletions.
        $state['receipts'][$m['mutation']]=['request'=>$m,'result'=>$result];
        $results[]=$result;
    }
    return ['protocol'=>1,'records'=>array_values($state['records']),'results'=>$results];
}
