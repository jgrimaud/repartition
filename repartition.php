<?php

/**
 * Class Repartition
 *
 * Used to divide in equal parts the money spent in a flat
 */
class Repartition
{
    /**
     * Assign flatmates
     *
     * @var array $_flatmates
     */
	private $_flatmates = [];

    /**
     * Creditors | AKA Positive average debt
     *
     * @var array $_creditors
     */
	private $_creditors = [];

    /**
     * Debtors | AKA Negative average debt
     *
     * @var array $_debtors
     */
	private $_debtors = [];

    /**
     * Used for the results
     *
     * @var array $_debtRepartition
     */
	private $_debtRepartition = [];

    /**
     * The global debt
     *
     * @var float $_globalDebt
     */
	private $_globalDebt = 0;

    /**
     * The number of flatmates | Captain obvious
     *
     * @var int $_flatmatesNumber
     */
	private $_flatmatesNumber = 0;

    /**
     * The average debt
     *
     * @var float|int
     */
	private $_averageDebt = 0;

    /**
     * Default contstructor
     *
     * @param   array       $flatmates
     * @throws  Exception
     */
	public function __construct(array $flatmates)
	{
		if (empty($flatmates)) {

			throw new Exception('This is not good brotha'); //Get another exception type
		}

		/*
		 * Init variables
		 */
		$this->_flatmates 		= $flatmates;
		$this->_globalDebt 		= $this->calculateGlobalDebt();
		$this->_flatmatesNumber = $this->calculateFlatmatesNumber();
		$this->_averageDebt		= $this->calculateAverageDebt();
	}

    /**
     * Public entry point
     *
     * @return array
     */
	public function getDebtRepartition()
	{
		return $this->_getDebtRepartition();
	}

    /**
     * Private entry point
     *
     * @return array
     */
	private function _getDebtRepartition()
	{
		$this->getAverageGap();

		/*
		 * Build the arrays
		 */
		$this->_creditors 	= $this->buildCreditors();
		$this->_debtors 	= $this->buildDebtors();

		$i = count($this->_debtors) - 1;

		while ($i >= 0) {

			if ($this->cleanDebt($i)) {

				$i--;
			}		
		}

		$results = [
			'debtRepartition' 	=> $this->_debtRepartition,
			'information'		=> [
				'globalDebt'	=> $this->_globalDebt,
				'averageDebt'	=> $this->_averageDebt
			]
		];

		return $results;
	}

    /**
     * Clean debt | Do the magic here
     *
     * @param   null    $debtorPosition
     * @return  bool
     */
	private function cleanDebt($debtorPosition = null)
	{
		if ($debtorPosition === null) {

			return false;
		}

		foreach ($this->_creditors as $key => $creditor) {
		
			$debtCalculation = $creditor['averageGap'] - abs($this->_debtors[$debtorPosition]['averageGap']);

			if ($debtCalculation > 0) {

				$this->_debtRepartition[$this->_debtors[$debtorPosition]['flatmateId']][] = [
					'creditor'	=> $creditor['flatmateId'],
					'amount'	=> abs($this->_debtors[$debtorPosition]['averageGap'])
				];

				$this->_creditors[$key]['averageGap'] = $creditor['averageGap'] - abs($this->_debtors[$debtorPosition]['averageGap']);

				unset($this->_debtors[$debtorPosition]);

				return true;
			}

			if ($debtCalculation < 0) {

				$this->_debtRepartition[$this->_debtors[$debtorPosition]['flatmateId']][] = [
					'creditor'	=> $creditor['flatmateId'],
					'amount'	=> $creditor['averageGap']
				];

				$this->_debtors[$debtorPosition]['averageGap'] = $creditor['averageGap'] - abs($this->_debtors[$debtorPosition]['averageGap']);

				unset($this->_creditors[$key]);

				return false;
			}

			if ($debtCalculation == 0) {

				$this->_debtRepartition[$this->_debtors[$debtorPosition]['flatmateId']][] = [
					'creditor'	=> $creditor['flatmateId'],
					'amount'	=> abs($this->_debtors[$debtorPosition]['averageGap'])
				];

                /*
                 * Delete both of them | They cancel their own debt
                 */
                unset($this->_debtors[$debtorPosition]);
				unset($this->_creditors[$key]);

				return true;
			}
		}

		return true; //TODO : Probably raise an exception here
	}

    /**
     * Get all the bad debtors
     *
     * @return array
     */
	private function buildDebtors()
	{
		$debtors = [];

		foreach ($this->_flatmates as $flatmate) {

			if ($flatmate['averageGap'] < 0) {

				$debtors[] = $flatmate;
			}
		}

		usort($debtors, function($a, $b) {

			return $b['averageGap'] - $a['averageGap'];
		});

		return $debtors;
	}

    /**
     * Build the nice creditors
     *
     * @return array
     */
	private function buildCreditors()
	{
		$creditors = [];

		foreach ($this->_flatmates as $flatmate) {

			if ($flatmate['averageGap'] > 0) {

				$creditors[] = $flatmate;
			}
		}

		usort($creditors, function($a, $b) {

			return $b['averageGap'] - $a['averageGap'];
		});

		return $creditors;
	}

    /**
     * Calculate flatmates number
     *
     * @return int
     */
	private function calculateFlatmatesNumber()
	{
		return count($this->_flatmates);
	}

    /**
     * Calculate average debt
     *
     * @return float
     */
	private function calculateAverageDebt()
	{
		return $this->_globalDebt / $this->_flatmatesNumber;
	}

    /**
     * Calculate global debt
     *
     * @return int
     */
	private function calculateGlobalDebt()
	{
		$globalDebt = 0;

		foreach ($this->_flatmates as $flatmate) {

			$globalDebt += $flatmate['expenses'];
		}

		return $globalDebt;
	}

    /**
     * Get the average gap
     *
     * @return void
     */
	private function getAverageGap() {

		foreach ($this->_flatmates as $key => $flatmate) {

			$this->_flatmates[$key]['averageGap'] = $flatmate['expenses'] - $this->_averageDebt;;
		}
	}
}

$flatmates = [
	0 => [
		'expenses' 		=> 8,
		'flatmateId'	=> 240
	],
	1 => [
		'expenses' 		=> 17,
		'flatmateId'	=> 350
	],
	2 => [
		'expenses' 		=> 19,
		'flatmateId'	=> 389
	],
	3 => [
		'expenses' 		=> 64,
		'flatmateId'	=> 264
	],
	4 => [
		'expenses' 		=> 31,
		'flatmateId'	=> 22
	],
	5 => [
		'expenses' 		=> 2,
		'flatmateId'	=> 44
	],
	6 => [
		'expenses' 		=> 44,
		'flatmateId'	=> 89
	],
	7 => [
		'expenses' 		=> 125,
		'flatmateId'	=> 743
	],
];

$repartition = new Repartition($flatmates);

$result = $repartition->getDebtRepartition();
