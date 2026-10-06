export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'seller' | 'financial';
    role_label: string;
    is_admin: boolean;
}

export interface Product {
    id: number;
    name: string;
    description?: string | null;
    price: number;
    stock: number;
    active: boolean;
    low_stock_threshold: number;
    is_low_stock?: boolean;
}

export interface Customer {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    address?: string | null;
    birth_date?: string | null;
    age?: number | null;
    sales_count?: number;
}

export interface PaymentMethod {
    id: number;
    name: string;
    description?: string | null;
    active: boolean;
}

export interface SaleItem {
    id?: number;
    product_id: number;
    product?: Product;
    quantity: number;
    unit_price: number;
    subtotal: number;
}

export interface SaleInstallment {
    id?: number;
    installment_number: number;
    amount: number;
    due_date: string;
    paid_date?: string | null;
    is_paid: boolean;
    status: 'pending' | 'paid' | 'overdue' | 'cancelled';
    notes?: string | null;
}

export interface Sale {
    id: number;
    customer_id?: number | null;
    customer?: Customer | null;
    payment_method_id: number;
    payment_method?: PaymentMethod;
    user_id: number;
    user?: User;
    status: 'pending' | 'completed' | 'cancelled';
    status_label: string;
    total_amount: number;
    discount: number;
    installments: number;
    notes?: string | null;
    items?: SaleItem[];
    sale_installments?: SaleInstallment[];
    created_at: string;
}

export interface ExpenseCategory {
    id: number;
    name: string;
    color: string;
    type: 'expense' | 'income';
}

export interface Expense {
    id: number;
    description: string;
    amount: number;
    due_date: string;
    paid_date?: string | null;
    type: 'expense' | 'income';
    type_label: string;
    status: 'pending' | 'paid' | 'overdue' | 'cancelled';
    status_label: string;
    category_id: number;
    category?: ExpenseCategory;
    notes?: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    links: {
        first?: string;
        last?: string;
        prev?: string | null;
        next?: string | null;
    };
    meta?: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
        links?: PaginationLink[];
    };
}

export interface CashMovement {
    id: number;
    user_id?: number;
    user?: { id: number; name: string } | null;
    type: 'supply' | 'bleed';
    amount: number;
    reason: string;
    created_at: string;
}

export interface CashShift {
    id: number;
    user_id?: number;
    user?: { id: number; name: string; email: string } | null;
    opened_at: string;
    closed_at?: string | null;
    initial_amount: number;
    final_amount_reported?: number | null;
    final_amount_expected?: number | null;
    difference?: number | null;
    status: 'open' | 'closed';
    notes?: string | null;
    sales_count?: number;
    movements?: CashMovement[];
    sales?: Array<{
        id: number;
        customer?: string | null;
        total_amount: number;
        created_at: string;
    }>;
}

export interface PageProps {
    auth: {
        user: (User & {
            current_cash_shift?: {
                id: number;
                opened_at: string;
                initial_amount: number;
            } | null;
        }) | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
        info?: string | null;
    };
    errors: Record<string, string>;
    [key: string]: unknown;
}
