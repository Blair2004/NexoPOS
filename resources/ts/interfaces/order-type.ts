export interface OrderType { 
    identifier: 'takeaway' | 'delivery' | 'dine-in'; 
    label: string;
    selected: boolean;
    icon: string;
};