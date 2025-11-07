<?php
/**
 * Copyright (c) 2025 Agustín Roizen.
 * Distributed under the Business Source License 1.1
 * (See accompanying file LICENSE or copy at https://github.com/bocho8/chronos/blob/main/LICENSE)
 */

class RouteHelper
{
    /**
     * Generate URL for a given route
     */
    public static function url($route, $params = [])
    {
        // Replace route parameters
        foreach ($params as $key => $value) {
            $route = str_replace('{' . $key . '}', $value, $route);
        }
        
        return $route;
    }
    
    /**
     * Generate dashboard URL based on user role
     */
    public static function getDashboardUrl($userRole)
    {
        return match ($userRole) {
            'ADMIN' => '/admin/dashboard',
            'DIRECTOR' => '/director/dashboard',
            'COORDINADOR' => '/coordinator/dashboard',
            'DOCENTE' => '/teacher/dashboard',
            'PADRE' => '/parent/dashboard',
            default => '/login'
        };
    }
    
    /**
     * Generate admin URLs
     */
    public static function getAdminUrls()
    {
        return [
            'users' => '/users',
            'teachers' => '/teachers',
            'subjects' => '/subjects',
            'schedules' => '/schedules',
            'schedule_management' => '/gestion-horarios',
            'groups' => '/groups',
            'publish_schedules' => '/schedules',
            'teacher_availability' => '/availability',
            'subject_assignments' => '/assignments',
            'reports' => '/reports',
            'students' => '/users', // Students are managed in users section
            'student_schedules' => '/schedules' // Will be handled by controller
        ];
    }
    
    /**
     * Generate coordinator URLs
     */
    public static function getCoordinatorUrls()
    {
        return [
            'teachers' => '/coordinator/teachers',
            'calendar' => '/coordinator/calendar',
            'teacher_availability' => '/availability',
            'subject_assignments' => '/assignments',
            'reports' => '/reports'
        ];
    }
    
    /**
     * Generate teacher URLs
     */
    public static function getTeacherUrls()
    {
        return [
            'my_schedule' => '/teacher/my-schedule',
            'my_availability' => '/teacher/my-availability'
        ];
    }
    
    /**
     * Generate parent URLs
     */
    public static function getParentUrls()
    {
        return [
            'students' => '/parent/students',
            'student_schedules' => '/parent/student-schedules'
        ];
    }
    
    /**
     * Get all URLs for a specific role
     */
    public static function getUrlsForRole($userRole)
    {
        return match ($userRole) {
            'ADMIN' => array_merge(
                self::getAdminUrls(),
                self::getCoordinatorUrls(),
                self::getTeacherUrls(),
                self::getParentUrls()
            ),
            'DIRECTOR' => array_merge(
                self::getAdminUrls(),
                self::getCoordinatorUrls()
            ),
            'COORDINADOR' => self::getCoordinatorUrls(),
            'DOCENTE' => self::getTeacherUrls(),
            'PADRE' => self::getParentUrls(),
            default => []
        };
    }
    
    /**
     * Check if current URL matches a route pattern
     */
    public static function isActive($currentPath, $routePattern, $exact = false)
    {
        if ($exact) {
            return $currentPath === $routePattern;
        }
        
        // Remove query string and fragments
        $currentPath = parse_url($currentPath, PHP_URL_PATH);
        
        // Check if current path starts with the route pattern
        return strpos($currentPath, $routePattern) === 0;
    }
    
    /**
     * Get current path from REQUEST_URI
     */
    public static function getCurrentPath()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }
}
