<?php
require_once 'config.php';

class PvkAssessmentService {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Calculate PVK development levels for a user and profession
     * @param int $userId
     * @param int $professionId
     * @return array Assessment results
     */
    public function calculatePvkLevels($userId, $professionId) {
        try {
            // Get all criteria linked to the profession
            $stmt = $this->pdo->prepare("
                SELECT ptc.criterion_id, ptc.criterion_weight, c.name as criterion_name
                FROM profession_to_criteria ptc
                JOIN pvk_criteria c ON ptc.criterion_id = c.id
                WHERE ptc.profession_id = ?
            ");
            $stmt->execute([$professionId]);
            $professionCriteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($professionCriteria)) {
                return ['error' => 'Для данной профессии не настроены критерии оценки'];
            }
            
            $pvkScores = [];
            $calculationDetails = [];
            
            // Process each criterion
            foreach ($professionCriteria as $criterion) {
                $criterionScore = $this->calculateCriterionScore($userId, $criterion['criterion_id']);
                
                if ($criterionScore !== null) {
                    $calculationDetails[$criterion['criterion_id']] = [
                        'criterion_name' => $criterion['criterion_name'],
                        'criterion_weight' => $criterion['criterion_weight'],
                        'raw_score' => $criterionScore['raw_score'],
                        'weighted_score' => $criterionScore['weighted_score'],
                        'indicators' => $criterionScore['indicators']
                    ];
                    
                    // Get PVKs linked to this criterion
                    $stmt = $this->pdo->prepare("
                        SELECT cp.pvk_id, pq.name as pvk_name
                        FROM criterion_to_pvk cp
                        JOIN professional_qualities pq ON cp.pvk_id = pq.id
                        WHERE cp.criterion_id = ?
                    ");
                    $stmt->execute([$criterion['criterion_id']]);
                    $linkedPvks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Apply criterion score to linked PVKs
                    foreach ($linkedPvks as $pvk) {
                        if (!isset($pvkScores[$pvk['pvk_id']])) {
                            $pvkScores[$pvk['pvk_id']] = [
                                'pvk_name' => $pvk['pvk_name'],
                                'scores' => [],
                                'weights' => []
                            ];
                        }
                        $pvkScores[$pvk['pvk_id']]['scores'][] = $criterionScore['weighted_score'];
                        $pvkScores[$pvk['pvk_id']]['weights'][] = $criterion['criterion_weight'];
                    }
                }
            }
            
            // Calculate final PVK assessment scores
            $finalAssessments = [];
            foreach ($pvkScores as $pvkId => $data) {
                $weightedSum = 0;
                $totalWeight = 0;
                
                for ($i = 0; $i < count($data['scores']); $i++) {
                    $weightedSum += $data['scores'][$i] * $data['weights'][$i];
                    $totalWeight += $data['weights'][$i];
                }
                
                $finalScore = $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
                $assessmentLevel = $this->getAssessmentLevel($finalScore);
                
                $finalAssessments[$pvkId] = [
                    'pvk_name' => $data['pvk_name'],
                    'assessment_score' => round($finalScore, 2),
                    'assessment_level' => $assessmentLevel
                ];
                
                // Save to database
                $this->savePvkAssessment($userId, $professionId, $pvkId, $finalScore, $assessmentLevel, $calculationDetails);
            }
            
            return [
                'success' => true,
                'assessments' => $finalAssessments,
                'calculation_details' => $calculationDetails
            ];
            
        } catch (Exception $e) {
            error_log("PVK Assessment Error: " . $e->getMessage());
            return ['error' => 'Ошибка при расчете уровня развития ПВК: ' . $e->getMessage()];
        }
    }
    
    /**
     * Calculate score for a specific criterion
     * @param int $userId
     * @param int $criterionId
     * @return array|null
     */
    private function calculateCriterionScore($userId, $criterionId) {
        // Get all indicators for this criterion
        $stmt = $this->pdo->prepare("
            SELECT * FROM criterion_test_indicators 
            WHERE criterion_id = ?
        ");
        $stmt->execute([$criterionId]);
        $indicators = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($indicators)) {
            return null;
        }
        
        $indicatorScores = [];
        $totalWeight = 0;
        $weightedSum = 0;
        
        foreach ($indicators as $indicator) {
            $userScore = $this->getUserTestScore($userId, $indicator['test_type'], $indicator['indicator_name']);
            
            if ($userScore !== null) {
                $normalizedScore = $this->normalizeScore($userScore, $indicator);
                $weightedScore = $normalizedScore * $indicator['indicator_weight'];
                
                $indicatorScores[] = [
                    'test_type' => $indicator['test_type'],
                    'indicator_name' => $indicator['indicator_name'],
                    'raw_value' => $userScore,
                    'normalized_score' => $normalizedScore,
                    'weight' => $indicator['indicator_weight'],
                    'weighted_score' => $weightedScore
                ];
                
                $weightedSum += $weightedScore;
                $totalWeight += $indicator['indicator_weight'];
            }
        }
        
        if ($totalWeight == 0) {
            return null;
        }
        
        $rawScore = $weightedSum / $totalWeight;
        
        return [
            'raw_score' => $rawScore,
            'weighted_score' => $rawScore, // For now, same as raw score
            'indicators' => $indicatorScores
        ];
    }
    
    /**
     * Get user's test score for a specific indicator
     * @param int $userId
     * @param string $testType
     * @param string $indicatorName
     * @return float|null
     */
    private function getUserTestScore($userId, $testType, $indicatorName) {
        // Get the most recent test session
        $stmt = $this->pdo->prepare("
            SELECT * FROM test_sessions 
            WHERE user_id = ? AND test_type = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId, $testType]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            return null;
        }
        
        // Extract the specific indicator value
        switch ($indicatorName) {
            case 'average_time':
                return $session['average_time'];
            case 'accuracy':
                return $session['accuracy'];
            case 'normalized_result':
                return $session['normalized_result'];
            default:
                // For custom indicators, might need more complex logic
                return $session['average_time']; // Fallback
        }
    }
    
    /**
     * Normalize score based on assessment direction and cutoff values
     * @param float $score
     * @param array $indicator
     * @return float Normalized score (0-10)
     */
    private function normalizeScore($score, $indicator) {
        // Apply cutoff check first
        if ($indicator['cutoff_value'] !== null && $indicator['cutoff_comparison_operator'] !== null) {
            $passedCutoff = $this->checkCutoff($score, $indicator['cutoff_value'], $indicator['cutoff_comparison_operator']);
            if (!$passedCutoff) {
                return 0; // Failed cutoff
            }
        }
        
        // Basic normalization - this should be enhanced with proper statistical methods
        if ($indicator['assessment_direction'] === 'higher_is_better') {
            // For accuracy, higher values are better
            if ($indicator['indicator_name'] === 'accuracy') {
                return min(10, max(0, $score / 10)); // Assuming accuracy is 0-100%
            } else {
                // For normalized_result, higher is better (1-3 scale)
                return min(10, max(0, $score * 3.33)); // Convert 1-3 to 0-10 scale
            }
        } else {
            // For reaction time, lower values are better
            if ($indicator['indicator_name'] === 'average_time') {
                // Inverse relationship - faster times get higher scores
                // This is a simplified approach - should use statistical normalization
                $maxTime = 5000; // 5 seconds max
                return min(10, max(0, 10 - ($score / $maxTime) * 10));
            }
        }
        
        return 5; // Default middle score
    }
    
    /**
     * Check if score passes cutoff criteria
     * @param float $score
     * @param float $cutoffValue
     * @param string $operator
     * @return bool
     */
    private function checkCutoff($score, $cutoffValue, $operator) {
        switch ($operator) {
            case '>=': return $score >= $cutoffValue;
            case '<=': return $score <= $cutoffValue;
            case '>': return $score > $cutoffValue;
            case '<': return $score < $cutoffValue;
            case '==': return abs($score - $cutoffValue) < 0.001; // Float comparison
            case '!=': return abs($score - $cutoffValue) >= 0.001;
            default: return true;
        }
    }
    
    /**
     * Convert numeric score to assessment level
     * @param float $score
     * @return string
     */
    private function getAssessmentLevel($score) {
        if ($score >= 8) return 'high';
        if ($score >= 6.5) return 'above_average';
        if ($score >= 3.5) return 'average';
        if ($score >= 2) return 'below_average';
        return 'low';
    }
    
    /**
     * Save PVK assessment to database
     * @param int $userId
     * @param int $professionId
     * @param int $pvkId
     * @param float $score
     * @param string $level
     * @param array $details
     */
    private function savePvkAssessment($userId, $professionId, $pvkId, $score, $level, $details) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_pvk_assessments 
            (user_id, profession_id, pvk_id, assessment_score, assessment_level, calculation_details, last_calculated)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (user_id, profession_id, pvk_id)
            DO UPDATE SET 
                assessment_score = EXCLUDED.assessment_score,
                assessment_level = EXCLUDED.assessment_level,
                calculation_details = EXCLUDED.calculation_details,
                last_calculated = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $userId,
            $professionId,
            $pvkId,
            $score,
            $level,
            json_encode($details)
        ]);
    }
    
    /**
     * Get existing assessments for a user and profession
     * @param int $userId
     * @param int $professionId
     * @return array
     */
    public function getExistingAssessments($userId, $professionId) {
        $stmt = $this->pdo->prepare("
            SELECT upa.*, pq.name as pvk_name
            FROM user_pvk_assessments upa
            JOIN professional_qualities pq ON upa.pvk_id = pq.id
            WHERE upa.user_id = ? AND upa.profession_id = ?
            ORDER BY upa.assessment_score DESC
        ");
        $stmt->execute([$userId, $professionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
