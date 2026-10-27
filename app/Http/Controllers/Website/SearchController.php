<?php

namespace App\Http\Controllers\Website;

use App\Models\Client;
use App\Models\Country;
use App\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class SearchController extends Controller
{
    private const SUGGESTION_LIMIT = 10;

    public function __invoke(Request $request): View|JsonResponse|RedirectResponse
    {
        // Validate input and retain old full-page search links without sharing the directory key.
        $filters = $request->validate([
            'afandy_search' => ['nullable', 'string', 'max:255'],
            'afandy_category' => ['nullable', 'string', 'in:stores,offers'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $isAjax = $request->ajax();
        $search = trim($filters['afandy_search'] ?? ($isAjax ? '' : ($filters['search'] ?? '')));
        $category = $filters['afandy_category'] ?? null;

        // Stop empty or single-character searches before querying the database.
        if (mb_strlen($search) < 2) {
            return $isAjax ? response()->json(['html' => '']) : redirect()->route('website.index');
        }

        // Prepare the current market and a case-normalized, literal LIKE search pattern.
        $country = website_country();
        $term = mb_strtolower($search);
        $pattern = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

        // Match active stores in the current market and order them by title relevance.
        $storeQuery = $this->availableStores(Client::query(), $country);
        $this->whereTitleMatches($storeQuery, $pattern);
        $this->orderByTitleRelevance($storeQuery, $term, $pattern);

        // Match available offers by their title or store name, keeping market restrictions grouped.
        $offerQuery = Offer::available()->with('client')
            ->whereHas('client', fn (Builder $query) => $this->availableStores($query, $country))
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $this->whereTitleMatches($query, $pattern))
                ->orWhereHas('client', fn (Builder $query) => $this->whereTitleMatches($query, $pattern)));
        $this->orderByTitleRelevance($offerQuery, $term, $pattern);

        // Count all matching types for the sidebar independently of the selected type and page.
        $counts = $isAjax ? [] : [
            'stores' => (clone $storeQuery)->reorder()->count(),
            'offers' => (clone $offerQuery)->reorder()->count(),
        ];
        if (! $isAjax && $category === null) {
            $category = $counts['offers'] > 0 || $counts['stores'] === 0 ? 'offers' : 'stores';
        }

        // Fetch only the selected types, limiting suggestions and paginating full results.
        $stores = collect();
        $offers = collect();
        if ($category !== 'offers') {
            $storeQuery->with('categories');
            // Count all available offers for each suggested store in the same query.
            if ($isAjax) $storeQuery->withCount(['offers as available_offers_count' => fn (Builder $query) => $query->available()]);
            $stores = $isAjax ? $storeQuery->limit(self::SUGGESTION_LIMIT)->get() : $storeQuery->paginate(12, ['*'], 'stores_page', total: $counts['stores'])->withQueryString();
        }
        if ($category !== 'stores') {
            if (! $isAjax) $offerQuery->with('client.categories');
            $offers = $isAjax ? $offerQuery->limit(self::SUGGESTION_LIMIT - $stores->count())->get() : $offerQuery->paginate(12, ['*'], 'offers_page', total: $counts['offers'])->withQueryString();
        }

        // Pass the selected type and the relevant groups to the existing result templates.
        $data = [
            'search' => $search,
            'category' => $category,
            'counts' => $counts,
            'stores' => $stores,
            'offers' => $offers,
            'storesFirst' => $stores->isNotEmpty() && $stores->first()->search_rank <= ($offers->first()?->search_rank ?? 3),
        ];

        // Render escaped Blade templates for the dropdown or the full results page.
        if ($isAjax) {
            return response()->json(['html' => view('website.search.partials.dropdown', $data)->render()]);
        }
        return view('website.search.index', $data);
    }

    private function availableStores(Builder $query, ?Country $country): Builder
    {
        // Reuse the same store visibility restrictions for store and offer queries.
        return $query->where('active', true)
            ->when($country, fn (Builder $query) => $query->whereHas('countries', fn (Builder $query) => $query->whereKey($country->id)));
    }

    private function titleExpressions(Builder $query): array
    {
        // Build identifiers only from model metadata and fixed supported translation keys.
        $grammar = $query->getQuery()->getGrammar();
        $column = $query->getModel()->qualifyColumn('title');
        return array_map(fn (string $locale) => 'LOWER(' . $grammar->wrap("{$column}->{$locale}") . ')', ['ar', 'en']);
    }

    private function whereTitleMatches(Builder $query, string $pattern): void
    {
        // Group both languages so OR conditions cannot bypass visibility restrictions.
        $query->where(function (Builder $query) use ($pattern) {
            foreach ($this->titleExpressions($query) as $title) {
                $query->orWhereRaw("{$title} LIKE ? ESCAPE '!'", ["%{$pattern}%"]);
            }
        });
    }

    private function orderByTitleRelevance(Builder $query, string $term, string $pattern): void
    {
        // Rank exact, prefix and contained matches using the same localized title expressions.
        $titles = $this->titleExpressions($query);
        $conditions = [];
        $bindings = [];
        foreach ([$term, "{$pattern}%", "%{$pattern}%"] as $rank => $value) {
            $operator = $rank === 0 ? '= ?' : "LIKE ? ESCAPE '!'";
            $comparisons = array_map(fn (string $title) => "{$title} {$operator}", $titles);
            $conditions[] = 'WHEN (' . implode(' OR ', $comparisons) . ") THEN {$rank}";
            array_push($bindings, ...array_fill(0, count($titles), $value));
        }

        // Bind every value and use a deterministic tie-breaker; store-only offer matches rank last.
        $query->select($query->getModel()->qualifyColumn('*'))
            ->selectRaw('CASE ' . implode(' ', $conditions) . ' ELSE 3 END AS search_rank', $bindings)
            ->orderBy('search_rank')->orderByDesc($query->getModel()->qualifyColumn('id'));
    }
}
