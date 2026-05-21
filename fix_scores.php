<?php
$criteria = \App\Models\Criteria::whereNull('submission_id')->orderBy('id')->get();
$alternatives = \App\Models\Alternative::orderBy('id')->get();

$rowVals = [];
// Retrieve exactly how they were inserted
foreach ($criteria as $crit) {
    foreach ($alternatives as $alt) {
        $score = \App\Models\Score::where('criteria_id', $crit->id)
            ->where('alternative_id', $alt->id)->first();
        $rowVals[] = $score ? $score->value : 0;
    }
}

// Now $rowVals contains the original CSV values from col 18 to 47
$idx = 0;
// Re-insert correctly: Outer loop Alternative, Inner loop Criteria
foreach ($alternatives as $alt) {
    foreach ($criteria as $crit) {
        $scoreVal = $rowVals[$idx];
        \App\Models\Score::updateOrCreate(
            ['alternative_id' => $alt->id, 'criteria_id' => $crit->id],
            ['value' => $scoreVal]
        );
        $idx++;
    }
}
echo "Scores fixed!\n";
