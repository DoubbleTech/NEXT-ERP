// components/SetupWizard.jsx

'use client'; 

import React, { useState } from 'react';

// Simplified Sales Tax Mapping (for demonstration)
const SALES_TAX_RATES = {
    'Pakistan': 17.00, 
    'Canada': 5.00,    
    'India': 18.00,    
    'Other': 0.00
};

// --- List of Business Industries ---
const BUSINESS_INDUSTRIES = [
    'Retail Store', 'Manufacturing Goods', 'Textile/Apparel', 
    'School/Education', 'Hospital/Healthcare', 'Professional Services'
];


// This component receives the Server Action via props
export default function SetupWizard({ completeSetupAction }) {
    const [status, setStatus] = useState('');
    const [formData, setFormData] = useState({
        // Default values for dropdowns/calculable fields
        companyCountry: 'Pakistan',
        operationCountry: 'Pakistan',
        salesTaxRate: SALES_TAX_RATES['Pakistan'],
        natureOfBusiness: 'Goods',
        // Text field defaults
        companyName: '',
        registrationNumber: '',
        registrationDate: new Date().toISOString().split('T')[0], // YYYY-MM-DD format
        taxIdentityNumber: '',
        businessIndustry: BUSINESS_INDUSTRIES[0],
        companyLogoUrl: '', // Placeholder for eventual upload URL
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        let newRate = formData.salesTaxRate;

        // Logic for auto-calculating tax rate based on operation country
        if (name === 'operationCountry') {
            newRate = SALES_TAX_RATES[value] || 0.00;
        }

        setFormData(prev => ({
            ...prev,
            [name]: value,
            salesTaxRate: newRate,
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setStatus('Saving...');

        // 1. Simple Validation Check (You should implement robust validation here)
        if (!formData.companyName || !formData.taxIdentityNumber) {
            setStatus('Error: Company Name and Tax ID are required.');
            return;
        }
        
        try {
            // 2. Call the Server Action directly
            const result = await completeSetupAction(formData); 

            if (result.success) {
                setStatus('Setup successful! Redirecting to dashboard...');
                alert("Company successfully registered!");
                // Reload the page to trigger the Server Component check again
                window.location.reload(); 
            } else {
                setStatus(`Setup failed: ${result.message}`);
            }
        } catch (error) {
            setStatus('An unexpected error occurred during submission.');
            console.error(error);
        }
    };

    return (
        <div style={{ padding: '40px', maxWidth: '900px', margin: 'auto', border: '1px solid #ccc', borderRadius: '8px', background: '#f9f9f9' }}>
            <h1 style={{ textAlign: 'center', marginBottom: '10px' }}>One-Time Company Registration Setup</h1>
            <p style={{ textAlign: 'center', color: '#888', marginBottom: '30px' }}>Please provide the required foundational data to begin operating the ERP.</p>
            
            <form onSubmit={handleSubmit} style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '25px' }}>
                
                {/* 1. Company Logo (Mock input) */}
                <div style={{ gridColumn: 'span 2', textAlign: 'center' }}>
                    <label style={{ display: 'block', marginBottom: '5px' }}>Company Logo URL</label>
                    <input type="text" name="companyLogoUrl" value={formData.companyLogoUrl} onChange={handleChange} placeholder="Optional Logo URL" style={{ width: '80%', padding: '8px' }} />
                </div>
                
                {/* 2. Company Name */}
                <div>
                    <label>Company Name *</label>
                    <input type="text" name="companyName" value={formData.companyName} onChange={handleChange} required style={{ width: '100%', padding: '8px' }} />
                </div>
                
                {/* 3. Company Registration # */}
                <div>
                    <label>Registration Number</label>
                    <input type="text" name="registrationNumber" value={formData.registrationNumber} onChange={handleChange} style={{ width: '100%', padding: '8px' }} />
                </div>
                
                {/* 4. Date of Registration */}
                <div>
                    <label>Date of Registration *</label>
                    <input type="date" name="registrationDate" value={formData.registrationDate} onChange={handleChange} required style={{ width: '100%', padding: '8px' }} />
                </div>
                
                {/* 5. Tax Identity Number */}
                <div>
                    <label>Tax Identity Number *</label>
                    <input type="text" name="taxIdentityNumber" value={formData.taxIdentityNumber} onChange={handleChange} required style={{ width: '100%', padding: '8px' }} />
                </div>
                
                {/* 6. Company Country */}
                <div>
                    <label>Company Country *</label>
                    <select name="companyCountry" value={formData.companyCountry} onChange={handleChange} required style={{ width: '100%', padding: '8px' }}>
                        <option value="Pakistan">Pakistan</option>
                        <option value="Canada">Canada</option>
                        <option value="India">India</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                {/* 7. Operation Country */}
                <div>
                    <label>Operation Country *</label>
                    <select name="operationCountry" value={formData.operationCountry} onChange={handleChange} required style={{ width: '100%', padding: '8px' }}>
                        <option value="Pakistan">Pakistan</option>
                        <option value="Canada">Canada</option>
                        <option value="India">India</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                {/* 8. Business Industry */}
                <div>
                    <label>Business Industry</label>
                    <select name="businessIndustry" value={formData.businessIndustry} onChange={handleChange} style={{ width: '100%', padding: '8px' }}>
                        {BUSINESS_INDUSTRIES.map(industry => (
                            <option key={industry} value={industry}>{industry}</option>
                        ))}
                    </select>
                </div>

                {/* 9. Nature of Business */}
                <div>
                    <label>Nature of Business *</label>
                    <select name="natureOfBusiness" value={formData.natureOfBusiness} onChange={handleChange} required style={{ width: '100%', padding: '8px' }}>
                        <option value="Goods">Goods</option>
                        <option value="Services">Services</option>
                        <option value="Both">Both (Goods & Services)</option>
                    </select>
                </div>

                {/* 10. Sales Tax Rate (Calculated/Display) */}
                <div style={{ gridColumn: 'span 2', padding: '15px', backgroundColor: '#e8f0fe', borderRadius: '4px', border: '1px solid #d0d8f0' }}>
                    <label>Calculated Default Sales Tax Rate:</label>
                    <h3 style={{ margin: '5px 0' }}>{formData.salesTaxRate}%</h3>
                    <p style={{ fontSize: '0.85em', color: '#333' }}>
                        (Rate based on **{formData.operationCountry}** for tax calculations.)
                    </p>
                </div>


                <div style={{ gridColumn: 'span 2', textAlign: 'center', marginTop: '10px' }}>
                    <button type="submit" disabled={status.includes('Saving') || status.includes('successful')} 
                        style={{ padding: '10px 20px', fontSize: '1.1em', background: '#0070f3', color: 'white', border: 'none', borderRadius: '5px', cursor: 'pointer' }}>
                        {status.includes('Saving') ? 'Processing...' : 'Complete Setup & Access Dashboard'}
                    </button>
                    <p style={{ color: status.includes('failed') ? 'red' : 'green', marginTop: '10px' }}>{status}</p>
                </div>

            </form>
        </div>
    );
}
