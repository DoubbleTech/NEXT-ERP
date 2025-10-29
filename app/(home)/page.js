// app/(home)/page.js
// This file is a Server Component (default in the App Router)

// --- 1. IMPORT NECESSARY COMPONENTS AND SERVER LOGIC ---
// Navigate UP two levels (out of (home) and out of app) to reach the 'components' folder.
import SetupWizard from '../../../components/SetupWizard'; 
import MainDashboard from '../../../components/Dashboard/YourMainDashboardComponent'; // 🛑 CHANGE THIS: Use the correct filename for your tiles UI!
import { checkSetupStatus, completeSetup } from './actions'; // Actions file is in the same directory

// Existing imports (Keep these for unauthenticated users)
import Form from '@/components/Home/Form'; // Assuming this path alias works for other components
import Logo from '@/components/Home/logo';
import React from 'react';

// --- ASSUMPTION: Your secure session/auth checker ---
async function getSession() {
    // 🛑 IMPLEMENT YOUR REAL AUTHENTICATION CHECK HERE.
    // If user is logged in: return { isLoggedIn: true, userId: 1 };
    // If user is NOT logged in: return { isLoggedIn: false };
    
    // MOCK FOR TESTING SETUP: We force the logged-in state to run the setup check.
    return { isLoggedIn: true, userId: 1 }; 
}
// --------------------------------------------------------


const Page = async () => {
    
    // Check authentication status
    const session = await getSession();

    // -----------------------------------------------------
    // PHASE 1: Authentication Check (For Unauthenticated Users)
    // -----------------------------------------------------
    if (!session.isLoggedIn) {
        // Renders your existing login/sign-up page content
        return (
            <section className='border-2 h-full my-10 max-w-6xl mx-auto shadow-[0_0_25px_rgba(0,0,0,0.2)] rounded-xl'>
                <div className="flex w-full gap-20">
                    {/* Logo */}
                    <div className='w-[500px] '>
                        <Logo />
                    </div>
                    {/* Form (Login/Sign-up) */}
                    <div className=' flex-1 pr-10'>
                        <Form />
                    </div>
                </div>
            </section>
        );
    }


    // -----------------------------------------------------
    // PHASE 2: Setup Check (For Authenticated Users)
    // -----------------------------------------------------
    
    // If the user IS logged in, check the setup status using the Server Action
    const status = await checkSetupStatus(session.userId); 

    if (status.setupRequired === true) {
        // If NO company is registered, render the client-side setup wizard.
        return (
            <div className="setup-container">
                <SetupWizard completeSetupAction={completeSetup} />
            </div>
        );
    } 
    
    if (status.setupRequired === 'selection') {
        // If multiple companies exist, redirect to a company selector page
        return <div>Redirecting to Company Selection...</div>;
    }

    // -----------------------------------------------------
    // PHASE 3: Dashboard Render (Setup Complete)
    // -----------------------------------------------------
    
    // If setup is complete, render the main dashboard UI (the tiles screen)
    return (
        <MainDashboard />
    );
};

export default Page;
