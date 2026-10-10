<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->redirectToRoute('admin_job_offer_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Job Scraper Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', null);
        yield MenuItem::section('Offres');
        yield MenuItem::linkToRoute('Job Offers', null, 'admin_job_offer_index');
        yield MenuItem::linkToRoute('Job Applications', null, 'admin_job_application_index');
        yield MenuItem::linkToRoute('Enterprises', null, 'admin_enterprise_index');
        yield MenuItem::linkToRoute('Contacts', null, 'admin_contact_index');
        yield MenuItem::section('Fichiers');
        yield MenuItem::linkToRoute('File References', null, 'admin_file_reference_index');
        yield MenuItem::section('Recherches');
        yield MenuItem::linkToRoute('Search Queries', null, 'admin_search_query_index');
    }
}

