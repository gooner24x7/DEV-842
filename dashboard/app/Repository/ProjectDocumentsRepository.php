<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\Questionnaire\ProjectDocument;
use App\Models\Questionnaire\ProjectDocumentCategory;
use App\Models\Questionnaire\ProjectDocumentGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProjectDocumentsRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    public function __construct()
    {

    }

    public function find(int $projectId, array $searchParams): Collection
    {
        $query = ProjectDocument::query()
            ->select([
                'project_documents.id',
                'project_documents.project_id',
                'project_documents.user_id',
                'project_documents.group_id',
                'project_documents.name',
                'project_documents.filename',
                'project_documents.description',
                'project_documents.created_at',
            ])
            ->join('project_document_groups', 'project_document_groups.id', '=', 'project_documents.group_id')
            ->where('project_documents.project_id', '=', $projectId);

        if (!empty($searchParams['search'])) {
            $query->where('project_documents.name', 'like', '%' . $searchParams['search'] . '%');
        }

        if (!empty($searchParams['category_id'])) {
            $query->where('project_document_groups.category_id', '=', $searchParams['category_id']);
        }

        if (!empty($searchParams['group_id'])) {
            $query->where('project_documents.group_id', '=', $searchParams['group_id']);
        }

        $query->orderBy('project_documents.id', 'desc');

        return $query->get();
    }

    public function get($id): ?ProjectDocument
    {
        return ProjectDocument::where(['id' => $id])->first();
    }

    public function getGroup(int $groupId): ?ProjectDocumentGroup
    {
        return ProjectDocumentGroup::where(['id' => $groupId])->first();
    }

    public function create(array $data): ?ProjectDocument
    {
        if (empty($data)) {
            return null;
        }

        $groupId = $data['group_id'] ?? null;

        if (empty($groupId)) {
            $group = ProjectDocumentGroup::create([
                'project_id' => $data['project_id'],
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'],
                'name' => $data['name'],
            ]);

            $groupId = $group->getId();
        }

        $document = ProjectDocument::create([
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'group_id' => $groupId,
            'name' => $data['name'],
            'filename' => $data['filename'],
            'description' => $data['description'],
        ]);

        return $document;
    }

    public function update(ProjectDocument $document, array $data): ?ProjectDocument
    {
        if (empty($data)) {
            return null;
        }

        $document->name = $data['name'];
        $document->save();


        return $document;
    }

    public function delete(ProjectDocument $document): bool
    {
        $deleted = Storage::disk('public')->delete('projects/' . $document->getId() . '/' . $document->getFilename());

        if (!$deleted) {
            return false;
        }

        return $document->delete();
    }

    public function deleteByGroupId(int $groupId): bool
    {
        $documents = ProjectDocument::where(['group_id' => $groupId])->get();

        foreach ($documents as $document) {
            $this->delete($document);
        }

        $group = ProjectDocumentGroup::where(['id' => $groupId])->first();

        return $group->delete();
    }

    public function getCategories(): array
    {
        $categories = ProjectDocumentCategory::query()->get()->toArray();
        $grouped = array_filter($categories, fn($category) => is_null($category['parent_id']));

        foreach($grouped as &$group) {
            $group['children'] = array_values(array_filter($categories, fn($category) => $category['parent_id'] === $group['id']));
        }

        return $grouped;
    }

    public function groupDocuments(Collection $items) : array
    {
        $array = [];
        $items = $items->groupBy('group_id')->toArray();

        foreach($items as $groupId => $group) {
            $array[] = [
                'group_id' => $groupId,
                'name' => $group[0]['name'],
                'total' => count($group),
                'created_at' => $group[0]['created_at'],
                'created_by' => $group[0]['created_by'],
                'documents' => $group
            ];
        }

        return $array;
    }
}
