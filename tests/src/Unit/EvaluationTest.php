<?php

namespace Drupal\Tests\smart_content_cdn\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\smart_content_block\Plugin\smart_content\Decision\MultipleBlockDecision;
use Drupal\smart_content\Plugin\smart_content\SegmentSetStorage\GlobalSegmentSet;
use Drupal\smart_content_cdn\DecisionEvaluator;

/**
 * Tests that ssr decision evaluations work as intended.
 *
 * @group smart_content_cdn
 */
class EvaluationTest extends UnitTestCase {

    /**
     * Multi-dimensional array of decisions to be evaluated,
     * keyed by condition type.
     */
    protected $decisions;

    /**
     * Decision evaluator object.
     */
    protected $evaluator;

    /**
     * Implements setUp().
     */
    protected function setUp(): void {
        parent::setUp();

        $attach_settings = [];

        // Settings for Is True condition type.
        $attach_settings['is_true'][] = [
            'segments' => [
                'segmentA' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'is_true' => [
                                    'field' => [
                                        'negate' => TRUE
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentB' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'is_true' => [
                                    'field' => [
                                        'negate' => TRUE
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentC' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'is_true' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Settings for Default segments.
        $attach_settings['default_segment'][] = [
            'segments' => [
                'segmentA' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'is_true' => [
                                    'field' => [
                                        'negate' => TRUE
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentB' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'is_true' => [
                                    'field' => [
                                        'negate' => TRUE
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentC' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'is_true' => [
                                    'field' => [
                                        'negate' => TRUE
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'decisions' => [
                'decision_id' => [
                    'default' => 'segmentC',
                ]
            ],
        ];

        // Settings for Geo condition type for equals op.
        $attach_settings['smart_cdn:geo'][] = [
            'segments' => [
                'segmentA' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'CA',
                                        'op' => 'equals',
                                        'smart_cdn' => [
                                            'value' => 'US',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentB' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'UK',
                                        'op' => 'equals',
                                        'smart_cdn' => [
                                            'value' => 'US',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentC' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'US',
                                        'op' => 'equals',
                                        'smart_cdn' => [
                                            'value' => 'US',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Settings for Geo condition type for contains op.
        $attach_settings['smart_cdn:geo'][] = [
            'segments' => [
                'segmentA' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'US',
                                        'op' => 'contains',
                                        'smart_cdn' => [
                                            'value' => '***U**S***',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentB' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'CA',
                                        'op' => 'contains',
                                        'smart_cdn' => [
                                            'value' => '****US****',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentC' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'US',
                                        'op' => 'contains',
                                        'smart_cdn' => [
                                            'value' => '****US****',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Settings for Geo condition type for start with op.
        $attach_settings['smart_cdn:geo'][] = [
            'segments' => [
                'segmentA' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'US',
                                        'op' => 'starts_with',
                                        'smart_cdn' => [
                                            'value' => '*US****',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentB' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'CA',
                                        'op' => 'starts_with',
                                        'smart_cdn' => [
                                            'value' => 'US****',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentC' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => 'US',
                                        'op' => 'starts_with',
                                        'smart_cdn' => [
                                            'value' => 'US****',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Settings for Geo condition type for empty op.
        $attach_settings['smart_cdn:geo'][] = [
            'segments' => [
                'segmentA' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => NULL,
                                        'op' => 'empty',
                                        'smart_cdn' => [
                                            'value' => 'US',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentB' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => NULL,
                                        'op' => 'empty',
                                        'field' => [
                                            'negate' => TRUE
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentC' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:geo' => [
                                    'settings' => [
                                        'value' => NULL,
                                        'op' => 'empty',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Settings for Interest condition type.
        $attach_settings['smart_cdn:interest'][] = [
            'segments' => [
                'segmentA' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:interest' => [
                                    'settings' => [
                                        'value' => '6',
                                        'smart_cdn' => [
                                            'value' => ['1', '2', '3', '4', '5'],
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentB' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:interest' => [
                                    'settings' => [
                                        'value' => '7',
                                        'smart_cdn' => [
                                            'value' => ['1', '2', '3', '4', '5'],
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'segmentC' => [
                    'conditions' => [
                        'group' => [
                            'conditions' => [
                                'smart_cdn:interest' => [
                                    'settings' => [
                                        'value' => '1',
                                        'smart_cdn' => [
                                            'value' => ['1', '2', '3', '4', '5'],
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Create segment storage set necessary for creating decisions.
        $segment_set_storage = $this->getMockBuilder(GlobalSegmentSet::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPluginId'])
            ->getMock();
        // Override getPluginId method.
        $segment_set_storage->method('getPluginId')
             ->willReturn('global_segment_set');

        // Loop through multidimensional $attach_settings array.
        foreach ($attach_settings as $key => $condition_settings) {
            // Loops through settings by condition type.
            foreach ($condition_settings as $settings) {
                // Create mock decision object.
                $decision = $this->getMockBuilder(MultipleBlockDecision::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods(['getAttachedSettings', 'getSegmentSetStorage'])
                    ->getMock();
                // Override getAttachedSettings method.
                $decision->method('getAttachedSettings')
                    ->willReturn($settings);
                // Override getSegmentSetStorage method.
                $decision->method('getSegmentSetStorage')
                    ->willReturn($segment_set_storage);

                // Add decision to multidimensional array by condition type.
                $this->decisions[$key][] = $decision;
            }
        }

        // Create decision evaluator object.
        $this->evaluator = new DecisionEvaluator();
    }

    /**
     * Test is_true condition type evaluation.
     */
    public function testEvaluateIsTrue(): void {
        $key = 'is_true';
        $this->_evaluateDecisions($key);
    }

    /**
     * Test default segment evaluation.
     */
    public function testEvaluateDefault(): void {
        $key = 'default_segment';
        $this->_evaluateDecisions($key);
    }

    /**
     * Test smart_cdn:geo condition type evaluation.
     */
    public function testEvaluateGeo(): void {
        $key = 'smart_cdn:geo';
        $this->_evaluateDecisions($key);
    }

    /**
     * Test smart_cdn:interest condition type evaluation.
     */
    public function testEvaluateInterest(): void {
        $key = 'smart_cdn:interest';
        $this->_evaluateDecisions($key);
    }

    protected function _evaluateDecisions($key): void {
        if (!empty($this->decisions[$key])) {
            // Loop through decisions by $key.
            foreach ($this->decisions[$key] as $decision) {
                // Get segment id from decision evaluation.
                $segment_id = $this->evaluator->evaluate($decision);
                $this->assertEquals('segmentC', $segment_id);
            }
        }
    }

    /**
      * Implements tearDown().
      */
    protected function tearDown(): void {
        parent::tearDown();

        unset($this->decisions);
        unset($this->evaluator);
    }
}
