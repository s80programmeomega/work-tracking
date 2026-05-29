<?php

namespace App\Services;

use App\Models\LabelTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class LabelTemplateService
{
    /**
     * Get all label templates
     */
    public function getAllTemplates(): Collection
    {
        return LabelTemplate::with('items')->get();
    }

    /**
     * Get templates by workflow type
     */
    public function getTemplatesByType(string $type): Collection
    {
        return LabelTemplate::with('items')
            ->where('type_workflow', $type)
            ->get();
    }

    /**
     * Get default template
     */
    public function getDefaultTemplate(): ?LabelTemplate
    {
        return LabelTemplate::with('items')
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get a specific template
     */
    public function getTemplate(int $templateId): LabelTemplate
    {
        return LabelTemplate::with('items')->findOrFail($templateId);
    }

    /**
     * Create a new label template
     */
    public function createTemplate(array $data): LabelTemplate
    {
        return DB::transaction(function () use ($data) {
            // Extract items data
            $itemsData = $data['items'] ?? [];
            unset($data['items']);

            // Set creator
            $data['created_by'] = $data['created_by'] ?? auth()->id();

            // If this is set as default, unset other defaults
            if ($data['is_default'] ?? false) {
                LabelTemplate::where('is_default', true)->update(['is_default' => false]);
            }

            // Create template
            $template = LabelTemplate::create($data);

            // Create template items
            if (! empty($itemsData)) {
                $this->createTemplateItems($template, $itemsData);
            }

            return $template->load('items');
        });
    }

    /**
     * Update a label template
     */
    public function updateTemplate(LabelTemplate $template, array $data): LabelTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            // Extract items data
            $itemsData = $data['items'] ?? null;
            unset($data['items']);

            // If this is set as default, unset other defaults
            if (($data['is_default'] ?? false) && ! $template->is_default) {
                LabelTemplate::where('is_default', true)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }

            // Update template
            $template->update($data);

            // Update items if provided
            if ($itemsData !== null) {
                // Delete existing items
                $template->items()->delete();

                // Create new items
                $this->createTemplateItems($template, $itemsData);
            }

            return $template->fresh(['items']);
        });
    }

    /**
     * Delete a label template
     */
    public function deleteTemplate(LabelTemplate $template): bool
    {
        return DB::transaction(function () use ($template) {
            // Delete all items
            $template->items()->delete();

            // Delete template
            return $template->delete();
        });
    }

    /**
     * Apply template to a project
     */
    public function applyTemplateToProject(LabelTemplate $template, int $projetId): array
    {
        return DB::transaction(function () use ($template, $projetId) {
            return $template->applyToProject($projetId);
        });
    }

    /**
     * Duplicate a template
     */
    public function duplicateTemplate(LabelTemplate $template, ?string $newName = null): LabelTemplate
    {
        return DB::transaction(function () use ($template, $newName) {
            // Create new template
            $newTemplate = $template->replicate();
            $newTemplate->nom = $newName ?? ($template->nom.' (copie)');
            $newTemplate->is_default = false;
            $newTemplate->created_by = auth()->id();
            $newTemplate->save();

            // Duplicate items
            foreach ($template->items as $item) {
                $newItem = $item->replicate();
                $newItem->label_template_id = $newTemplate->id;
                $newItem->save();
            }

            return $newTemplate->load('items');
        });
    }

    /**
     * Set template as default
     */
    public function setAsDefault(LabelTemplate $template): LabelTemplate
    {
        return DB::transaction(function () use ($template) {
            // Unset all other defaults
            LabelTemplate::where('is_default', true)->update(['is_default' => false]);

            // Set this as default
            $template->update(['is_default' => true]);

            return $template->fresh();
        });
    }

    /**
     * Create template items for a template
     */
    private function createTemplateItems(LabelTemplate $template, array $itemsData): void
    {
        foreach ($itemsData as $index => $itemData) {
            $template->items()->create([
                'nom' => $itemData['nom'],
                'couleur' => $itemData['couleur'],
                'description' => $itemData['description'] ?? null,
                'ordre' => $itemData['ordre'] ?? $index,
            ]);
        }
    }

    /**
     * Get predefined templates (for seeding or quick setup)
     */
    public function getPredefinedTemplates(): array
    {
        return [
            [
                'nom' => 'Agile/Scrum',
                'description' => 'Labels pour méthodologie Agile/Scrum',
                'type_workflow' => 'agile',
                'is_default' => true,
                'items' => [
                    ['nom' => 'Backlog', 'couleur' => '#94A3B8', 'ordre' => 0],
                    ['nom' => 'À faire', 'couleur' => '#3B82F6', 'ordre' => 1],
                    ['nom' => 'En cours', 'couleur' => '#F59E0B', 'ordre' => 2],
                    ['nom' => 'En révision', 'couleur' => '#8B5CF6', 'ordre' => 3],
                    ['nom' => 'Terminé', 'couleur' => '#10B981', 'ordre' => 4],
                    ['nom' => 'Bloqué', 'couleur' => '#EF4444', 'ordre' => 5],
                ],
            ],
            [
                'nom' => 'Kanban Simple',
                'description' => 'Labels simples pour tableau Kanban',
                'type_workflow' => 'kanban',
                'is_default' => false,
                'items' => [
                    ['nom' => 'À faire', 'couleur' => '#64748B', 'ordre' => 0],
                    ['nom' => 'En cours', 'couleur' => '#3B82F6', 'ordre' => 1],
                    ['nom' => 'Fait', 'couleur' => '#10B981', 'ordre' => 2],
                ],
            ],
            [
                'nom' => 'Priorités',
                'description' => 'Labels basés sur les priorités',
                'type_workflow' => 'custom',
                'is_default' => false,
                'items' => [
                    ['nom' => 'Critique', 'couleur' => '#DC2626', 'ordre' => 0],
                    ['nom' => 'Haute', 'couleur' => '#F59E0B', 'ordre' => 1],
                    ['nom' => 'Moyenne', 'couleur' => '#3B82F6', 'ordre' => 2],
                    ['nom' => 'Basse', 'couleur' => '#10B981', 'ordre' => 3],
                ],
            ],
        ];
    }

    /**
     * Seed predefined templates
     */
    public function seedPredefinedTemplates(): SupportCollection
    {
        $templates = collect();

        DB::transaction(function () use (&$templates) {
            foreach ($this->getPredefinedTemplates() as $templateData) {
                $templates->push($this->createTemplate($templateData));
            }
        });

        return $templates;
    }
}
