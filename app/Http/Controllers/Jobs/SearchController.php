<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Dependency\Benefits;
use App\Models\Dependency\Categories;
use App\Models\Dependency\Location;
use App\Models\Dependency\Skills;
use App\Models\Jobs\Job;
use App\Http\Resources\Jobs\JobResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = Job::query();

        // Filter jobs based on user role
        switch ($request->user()->role) {
            case 'candidate':
                $query->where('status', 'open');
                break;
            case 'employer':
                $query->where('status', 'open');
                break;
        }

        if ($request->filled('query')) {
            $query->where(function ($q) use ($request) {
                $q->where('job_title', 'LIKE', '%' . $request->query('query') . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->query('query') . '%');
            });
        }

        if ($request->filled('location')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->query('location') . '%');
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->query('job_category') . '%');
            });
        }

        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->query('experience_level'));
        }

        if ($request->filled('salary_from')) {
            $query->where('salary_from', '>=', $request->query('salary_from'));
        }

        if ($request->filled('salary_to')) {
            $query->where('salary_to', '<=', $request->query('salary_to'));
        }

        if ($request->filled('work_type')) {
            $query->where('work_type', '=', $request->query('work_type'));
        }

        if ($request->filled('created_at')) {
			$query->where('created_at', '<=', Carbon::now());
        }

        $jobs = JobResource::collection($query->get());

        return response()->json($jobs);
    }

    public function autocomplete(Request $request)
    {
        // Validator
        $validator = Validator::make($request->all(), [
            'query' => 'required|string',
            'searchtype' => 'required|string|in:skill,skills,location,locations,category,categories,benefit,benefits'
        ]);

        // Validate
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Search
        $searchType = $request->query('searchtype');
        $keyword = $request->query('query');

        // Get query
        $query = null;
        switch ($searchType) {
            case 'skill':
            case 'skills':
                $query = Skills::query();
                if ($keyword !== 'all') $query->where('name', 'LIKE', $keyword . '%')->limit(5);
                break;
            case 'location':
            case 'locations':
                $query = Location::query();
                if ($keyword !== 'all') $query->where('name', 'LIKE', $keyword . '%')->limit(5);
                break;
            case 'category':
            case 'categories':
                $query = Categories::query();
                if ($keyword !== 'all') $query->where('name', 'LIKE', $keyword . '%')->limit(5);
                break;
            case 'benefit':
            case 'benefits':
                $query = Benefits::query();
                if ($keyword !== 'all') $query->where('name', 'LIKE', $keyword . '%')->limit(5);
                break;
            default:
                return response()->json([
                    'message' => 'Invalid search type'
                ], 422);
                break;
        }

        // Get results
        $results = $query->get();
        return response()->json($results, 200);
    }

    public function locations() {
        return response()->json(Location::all());
    }

    public function skills() {
        return response()->json(Skills::all());
    }

    public function benefits() {
        return response()->json(Benefits::all());
    }

    public function categories() {
        return response()->json(Categories::all());
    }
}
