<?php

namespace App\Http\Controllers;

use App\Models\Users\Application;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\SubmittedJobResource;
use App\Models\Users\CVApplication;
use App\Models\Users\FormApplication;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get data from the database with role
        $applications = [];
        switch ($request->user()->role) {
            case 'admin':
                $applications_data = Application::all();
                $applications = ApplicationResource::collection($applications_data);
                break;
            case 'candidate':
                $applications_data = Application::where('candidate_id', $request->user()->id)->get();
                $applications = SubmittedJobResource::collection($applications_data);
                break;
        }

        // handel if no applicatons found
        if (count($applications) == 0) {
            return response()->json(["message"=> "No applications found"], 404);
        }

        // Return the data
        return response()->json($applications, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create', Application::class)) {
            // Return a 403 error if the user doesn't have permission
            return response()->json([
                'error' => 'You do not have permission to apply for a job, only candidates can apply for jobs'
            ], 403);
        }

        // Validate the input
        $validatedData = $request->validate([
            'type' => 'required|string|in:cv,form',
            'job_id' => 'required|exists:job_listings,id',
            'cv' => ['mimes:doc,pdf,docx', Rule::requiredIf(function () use ($request) {
                return $request->type == 'cv';
            })],
            'name' => ['string', Rule::requiredIf(function () use ($request) {
                return $request->type == 'form';
            })],
            'email' => ['email', Rule::requiredIf(function () use ($request) {
                return $request->type == 'form';
            })],
            'phone_number' => ['string', Rule::requiredIf(function () use ($request) {
                return $request->type == 'form';
            })],
        ]);


        // add transction
        DB::beginTransaction();
        try {
            // Add the user as the candidate
            $validatedData['candidate_id'] = $request->user()->id;
            $validatedData['status'] = 'pending';
            $application = Application::create($validatedData)->refresh();
            
            if ($application->type == 'cv' && isset($validatedData['cv'])) {
                $cv_path = $validatedData['cv']->store("", 'job_cv');

                CVApplication::create([
                    'application_id'=>$application->id, 
                    'cv'=> $cv_path
                ]);
            }
            if ($application->type == 'form') {
                FormApplication::create([
                    'application_id'=>$application->id, 
                    'name'=>$validatedData['name'],
                    'email'=>$validatedData['email'],
                    'phone_number'=>$validatedData['phone_number']
                ]);
            }

            // add count applications to job
            $application->job->update(['number_of_applications' => $application->job->number_of_applications + 1]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
        return response()->json(['message'=>'application was submitted successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Application $application, Request $request)
    {
        if (!$request->user()->can('view', $application)) {
            // Return a 403 error if the user doesn't have permission
            return response()->json([
                'error' => 'You do not have permission to view this application'
            ], 403);
        }

        return response()->json(new ApplicationResource($application), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Application $application, Request $request)
    {
        if (!$request->user()->can('delete', $application)) {
            // Return a 403 error if the user doesn't have permission
            return response()->json([
                'error' => 'You do not have permission to view this application'
            ], 403);
        }
        
        // delete cv application
        if ($application->type == 'cv') {
            $cv = CVApplication::where('application_id', $application->id)->first();
            // delete from storage
            $cv_path = $cv->cv;
            if ($cv_path) {
                Storage::disk('job_cv')->delete($cv_path);
            }
            // delete from database
            $cv->delete();
        }

        // delete form application
        if ($application->type == 'form') {
            $form = FormApplication::where('application_id', $application->id)->first();
            $form->delete();
        }

        // delete application
        $application->job->update(['number_of_applications' => $application->job->number_of_applications > 0 ? $application->job->number_of_applications - 1 : 0]);
        $application->delete();

        return response()->json(['message' => 'application deleted successfully']);
    }
}
