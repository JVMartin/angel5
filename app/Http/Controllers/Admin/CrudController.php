<?php
/**
 * @copyright (c) 2016 Jacob Martin
 * @license MIT https://opensource.org/licenses/MIT
 */
 
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Admin\CrudRepository;
use App\Exceptions\Admin\InvalidRepositoryException;

abstract class CrudController extends Controller
{
	/**
	 * @var The appropriate CrudRepository; filled using setRepository.
	 */
	protected $repository;

	/**
	 * Ensure that the subclass sets an appropriate repository.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setRepository();

		if ( ! $this->repository) {
			throw new InvalidRepositoryException(
				"You must properly implement setRepository() in " . static::class
			);
		}
		if ( ! $this->repository instanceof CrudRepository) {
			throw new InvalidRepositoryException(
				"setRepository() in " . static::class . " must set an instance of " .
				CrudRepository::class
			);
		}

		$this->data['repository'] = $this->repository;
	}

	/**
	 * Set $this->repository to an instance of CrudRepository.
	 *
	 * @return void
	 */
	abstract protected function setRepository();

	/**
	 * Get an index of all of the items in this module.
	 *
	 * Note that you can easily use your own custom view by placing it here:
	 * resources/views/admin/{handle}/index.blade.php
	 */
	public function getIndex()
	{
		$models = $this->repository->index();
		$this->data['models'] = $models;
		if (view()->exists('admin.' . $this->repository->getHandle() . '.index')) {
			return view('admin.' . $this->repository->getHandle() . '.index', $this->data);
		}
		return view('admin.crud.index', $this->data);
	}

	/**
	 * When the user uses the index search bar.
	 */
	public function postSearch(Request $request)
	{
		session(['admin.' . $this->repository->getHandle() . '.search' => $request->search]);
		return redirect()->back();
	}

	/**
	 * When the user clicks on a column heading.  Order by that column; toggle between ascending
	 * and descending order.
	 */
	public function getOrderBy($column)
	{
		$direction = 'ASC';

		$columnKey    = 'admin.' . $this->repository->getHandle() . '.order.column';
		$directionKey = 'admin.' . $this->repository->getHandle() . '.order.direction';

		// Toggle the direction if they clicked on the same column again.
		$oldColumn    = session($columnKey);
		$oldDirection = session($directionKey);
		if ($oldColumn == $column) {
			$direction = ($oldDirection == 'ASC') ? 'DESC' : 'ASC';
		}

		// Update the session.
		session([$columnKey    => $column]);
		session([$directionKey => $direction]);

		return redirect()->back();
	}

	/**
	 * Present a form for adding a new item to the database.
	 *
	 * Note that you can easily use your own custom view by placing it here:
	 * resources/views/admin/{handle}/add-or-edit.blade.php
	 */
	public function getAdd()
	{
		$this->data['action'] = 'add';
		if (view()->exists('admin.' . $this->repository->getHandle() . '.add-or-edit')) {
			return view('admin.' . $this->repository->getHandle() . '.add-or-edit', $this->data);
		}
		return view('admin.crud.add-or-edit', $this->data);
	}

	/**
	 * Add a new item to the database.
	 */
	public function postAdd(Request $request)
	{
		$this->validate($request, $this->repository->getValidationRules(), [], $this->repository->getValidationAttributes());
		$model = $this->repository->create($request);
		$this->redirectSuccessMessage($this->repository->getSingular() . ' created.');
		return redirect()->to($model->editURL())
			->with('successes', $this->successes);
	}

	/**
	 * Present a form for editing an existing item in the database.
	 *
	 * Note that you can easily use your own custom view by placing it here:
	 * resources/views/admin/{handle}/add-or-edit.blade.php
	 */
	public function getEdit($id)
	{
		$model = $this->repository->find($id);
		$this->data['action'] = 'edit';
		$this->data['model']  = $model;
		if (view()->exists('admin.' . $this->repository->getHandle() . '.add-or-edit')) {
			return view('admin.' . $this->repository->getHandle() . '.add-or-edit', $this->data);
		}
		return view('admin.crud.add-or-edit', $this->data);
	}

	/**
	 * Edit an existing item in the database.
	 */
	public function postEdit(Request $request, $id)
	{
		$this->validate($request, $this->repository->getValidationRules($id), [], $this->repository->getValidationAttributes());
		$model = $this->repository->find($id);
		$this->repository->logChanges($model, $request->all());
		$model->update($request->all());
		$this->redirectSuccessMessage($this->repository->getSingular() . ' saved.');
		return redirect()->back()->with('successes', $this->successes);
	}
}
