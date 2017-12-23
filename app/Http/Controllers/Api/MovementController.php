<?php

namespace Koodilab\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Koodilab\Http\Controllers\Controller;
use Koodilab\Models\Movement;
use Koodilab\Models\Planet;
use Koodilab\Models\Population;
use Koodilab\Models\Unit;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MovementController extends Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('player');
    }

    /**
     * Store a newly created movement in storage.
     *
     * @param Planet $planet
     *
     * @return mixed|\Illuminate\Http\Response
     */
    public function storeScout(Planet $planet)
    {
        $this->authorize('hostile', $planet);

        $quantity = $this->quantity();

        /** @var \Koodilab\Models\Population $population */
        $population = auth()->user()->current->findPopulationByUnit(
            Unit::findByType(Unit::TYPE_SCOUT)
        );

        if (!$population || !$population->hasQuantity($quantity)) {
            throw new BadRequestHttpException();
        }

        DB::transaction(function () use ($planet, $population, $quantity) {
            Movement::createScoutFrom(
                $planet, $population, $quantity
            );
        });
    }

    /**
     * Store a newly created movement in storage.
     *
     * @param Planet $planet
     *
     * @return mixed|\Illuminate\Http\Response
     */
    public function storeAttack(Planet $planet)
    {
        $this->authorize('hostile', $planet);

        $quantities = $this->quantities();

        $populations = auth()->user()->current->findPopulationsByUnitIds($quantities->keys())
            ->filter(function (Population $population) {
                return in_array($population->unit->type, [
                    Unit::TYPE_FIGHTER, Unit::TYPE_HEAVY_FIGHTER,
                ]);
            })
            ->each(function (Population $population) use ($quantities) {
                if (!$population->hasQuantity($quantities->get($population->unit_id))) {
                    throw new BadRequestHttpException();
                }
            });

        DB::transaction(function () use ($planet, $populations, $quantities) {
            Movement::createAttackFrom(
                $planet, $populations, $quantities
            );
        });
    }

    /**
     * Store a newly created movement in storage.
     *
     * @param Planet $planet
     *
     * @return mixed|\Illuminate\Http\Response
     */
    public function storeOccupy(Planet $planet)
    {
        $this->authorize('hostile', $planet);

        /** @var \Koodilab\Models\User $user */
        $user = auth()->user();

        if (!$user->canOccupy($planet)) {
            throw new BadRequestHttpException();
        }

        /** @var \Koodilab\Models\Population $population */
        $population = $user->current->findPopulationByUnit(
            Unit::findByType(Unit::TYPE_SETTLER)
        );

        if (!$population || !$population->hasQuantity(Planet::SETTLER_COUNT)) {
            throw new BadRequestHttpException();
        }

        DB::transaction(function () use ($planet, $population) {
            Movement::createOccupyFrom(
                $planet, $population
            );
        });
    }

    public function storeSupport(Planet $planet)
    {
        $this->authorize('friendly', $planet);

        $quantities = $this->quantities();
    }

    public function storeTransport(Planet $planet)
    {
        $this->authorize('friendly', $planet);

        $quantities = $this->quantities();
    }
}
