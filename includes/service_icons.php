<?php
/**
 * Service Icons Library
 * Provides a centralized collection of service icons
 */

class ServiceIcons {
    
    public static function getAvailableIcons() {
        return [
            'wrench' => '🔧',
            'electrical' => '⚡',
            'home' => '🏠',
            'cleaning' => '🧹',
            'snowflake' => '❄️',
            'garden' => '🌿',
            'pest' => '🪲',
            'paint' => '🎨',
            'appliance' => '🔌',
            'security' => '🛡️',
            'roof' => '🏗️',
            'saw' => '🪚',
            'hammer' => '🔨',
            'drill' => '🪛',
            'fire' => '🔥',
            'water' => '💧',
            'window' => '🪟',
            'door' => '🚪',
            'ladder' => '🪜',
            'toolbox' => '🧰',
            'gear' => '⚙️',
            'lock' => '🔒',
            'key' => '🔑',
            'truck' => '🚚',
            'car' => '🚗',
            'shield' => '🛡️',
            'light' => '💡',
            'camera' => '📹',
            'bell' => '🔔',
            'phone' => '📞'
        ];
    }
    
    public static function getServiceIcon($serviceName, $iconKey = null) {
        $icons = self::getAvailableIcons();
        
        // If specific icon key provided and exists
        if ($iconKey && isset($icons[$iconKey])) {
            return $icons[$iconKey];
        }
        
        // Fallback to name-based mapping
        $nameMap = [
            'plumbing' => 'wrench',
            'electrical' => 'electrical',
            'electrician' => 'electrical',
            'cleaning' => 'cleaning',
            'gardening' => 'garden',
            'pest control' => 'pest',
            'painting' => 'paint',
            'appliance repair' => 'appliance',
            'home security' => 'security',
            'roofing' => 'roof',
            'carpentry' => 'saw',
            'hvac' => 'snowflake',
            'heating' => 'fire',
            'cooling' => 'snowflake',
            'hvac services' => 'snowflake',
            'home repair' => 'hammer'
        ];
        
        $key = strtolower(trim($serviceName));
        $iconKey = $nameMap[$key] ?? 'toolbox';
        
        return $icons[$iconKey] ?? '🛠️';
    }
    
    public static function getIconByKey($iconKey) {
        $icons = self::getAvailableIcons();
        return $icons[$iconKey] ?? '🛠️';
    }
}
?>