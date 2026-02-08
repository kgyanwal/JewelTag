<?php

namespace App\Http\Controllers;

use App\Services\ZebraPrinterService;
use App\Models\LabelLayout;
use Illuminate\Http\Request;

class LabelLayoutController extends Controller
{
    protected $printerService;
    
    public function __construct()
    {
        $this->printerService = new ZebraPrinterService();
    }
    
    /**
     * Set default label layout positions
     */
    public function setDefaultLayout(Request $request)
    {
        try {
            $result = $this->printerService->setDefaultLayout();
            
            if ($result) {
                // Also return the new layout data
                $layouts = LabelLayout::all();
                return response()->json([
                    'success' => true, 
                    'message' => 'Default layout set successfully',
                    'layouts' => $layouts
                ]);
            } else {
                return response()->json([
                    'success' => false, 
                    'message' => 'Failed to set default layout'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get current layout settings
     */
    public function getLayouts()
    {
        try {
            $layouts = LabelLayout::all();
            return response()->json([
                'success' => true,
                'layouts' => $layouts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a specific layout field
     */
    public function updateLayout(Request $request, $fieldId)
    {
        try {
            $validated = $request->validate([
                'x_pos' => 'nullable|numeric',
                'y_pos' => 'nullable|numeric',
                'font_size' => 'nullable|integer',
                'height' => 'nullable|numeric',
                'width' => 'nullable|numeric',
            ]);
            
            $layout = LabelLayout::where('field_id', $fieldId)->first();
            
            if (!$layout) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Layout field not found'
                ], 404);
            }
            
            $layout->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Layout updated successfully',
                'layout' => $layout
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}