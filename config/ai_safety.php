<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ngưỡng chặn phản hồi rủi ro
    |--------------------------------------------------------------------------
    */
    'risk_block_threshold' => 0.35,

    /*
    |--------------------------------------------------------------------------
    | Trọng số cho rubric chấm điểm (LLM-as-a-judge mô phỏng nội bộ)
    |--------------------------------------------------------------------------
    */
    'rubric_weights' => [
        'factuality' => 0.30,
        'instruction_compliance' => 0.20,
        'safety' => 0.25,
        'coverage_edge_cases' => 0.15,
        'tool_use_quality' => 0.10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Các detector mặc định bật
    |--------------------------------------------------------------------------
    */
    'enabled_detectors' => [
        'prompt_injection',
        'jailbreak',
        'secret_leakage',
        'toxicity',
        'bias',
        'policy_violation',
        'hallucination',
    ],
];
