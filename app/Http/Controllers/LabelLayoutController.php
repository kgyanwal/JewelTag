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
     * Update a specific layout field from UI
     */
    public function updateLayout(Request $request, $fieldId)
    {
        try {
            $validated = $request->validate([
                'x_pos' => 'required|numeric',
                'y_pos' => 'required|numeric',
                'font_size' => 'required|integer|min:1|max:10',
                'height' => 'nullable|numeric',  // Can be decimal for barcode
                'width' => 'nullable|numeric',   // Can be decimal for barcode
            ]);
            
            $result = $this->printerService->saveLayoutFromDesigner($fieldId, $validated);
            
            if ($result) {
                $layout = LabelLayout::where('field_id', $fieldId)->first();
                return response()->json([
                    'success' => true,
                    'message' => 'Layout updated successfully',
                    'layout' => $layout
                ]);
            } else {
                return response()->json([
                    'success' => false, 
                    'message' => 'Failed to update layout'
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
     * Save all layouts at once from designer UI
     */
    public function saveAllLayouts(Request $request)
    {
        try {
            $validated = $request->validate([
                'layouts' => 'required|array',
            ]);
            
            $result = $this->printerService->saveAllLayouts($validated['layouts']);
            
            if ($result) {
                $layouts = LabelLayout::all();
                return response()->json([
                    'success' => true,
                    'message' => 'All layouts saved successfully',
                    'layouts' => $layouts
                ]);
            } else {
                return response()->json([
                    'success' => false, 
                    'message' => 'Failed to save layouts'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}