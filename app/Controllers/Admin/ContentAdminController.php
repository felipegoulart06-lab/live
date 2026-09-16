<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\Admin\AdminPanelRepository;
use App\Services\Admin\AdminOpsService;

final class ContentAdminController extends AdminController
{
    public function __construct(
        private readonly AdminPanelRepository $panel = new AdminPanelRepository(),
        private readonly AdminOpsService $ops = new AdminOpsService()
    ) {
    }

    public function banners(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }

        return $this->panel('admin/content/banners', [
            'title' => 'Banners',
            'rows' => $this->panel->banners(),
            'editing' => $request->query('id') ? $this->panel->banner((int) $request->query('id')) : null,
        ]);
    }

    public function saveBanner(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $id = (int) $request->input('id', 0) ?: null;
        $result = $this->ops->saveBanner($id, $request->all());
        $this->withSuccess($result['ok'] ? 'Banner salvo.' : ($result['message'] ?? 'Falha.'));
        if ($result['ok']) {
            $this->audit('banner.save', 'banner', (int) $result['id']);
        }

        return $this->redirect('/admin/banners');
    }

    public function deleteBanner(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $this->ops->deleteRow('banners', (int) $request->param('id'));
        $this->withSuccess('Banner removido.');

        return $this->redirect('/admin/banners');
    }

    public function faqs(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }

        return $this->panel('admin/content/faqs', [
            'title' => 'Perguntas frequentes',
            'rows' => $this->panel->faqs(),
            'editing' => $request->query('id') ? $this->panel->faq((int) $request->query('id')) : null,
        ]);
    }

    public function saveFaq(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $id = (int) $request->input('id', 0) ?: null;
        $result = $this->ops->saveFaq($id, $request->all());
        $this->withSuccess($result['ok'] ? 'FAQ salva.' : ($result['message'] ?? 'Falha.'));

        return $this->redirect('/admin/faqs');
    }

    public function deleteFaq(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $this->ops->deleteRow('faqs', (int) $request->param('id'));
        $this->withSuccess('FAQ removida.');

        return $this->redirect('/admin/faqs');
    }

    public function testimonials(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }

        return $this->panel('admin/content/testimonials', [
            'title' => 'Depoimentos',
            'rows' => $this->panel->testimonials(),
            'editing' => $request->query('id') ? $this->panel->testimonial((int) $request->query('id')) : null,
        ]);
    }

    public function saveTestimonial(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $id = (int) $request->input('id', 0) ?: null;
        $result = $this->ops->saveTestimonial($id, $request->all());
        $this->withSuccess($result['ok'] ? 'Depoimento salvo.' : ($result['message'] ?? 'Falha.'));

        return $this->redirect('/admin/depoimentos');
    }

    public function deleteTestimonial(Request $request): Response
    {
        if ($denied = $this->canOrDeny('cms.manage')) {
            return $denied;
        }
        $this->ops->deleteRow('testimonials', (int) $request->param('id'));
        $this->withSuccess('Depoimento removido.');

        return $this->redirect('/admin/depoimentos');
    }
}
